package cz.lriedel.photo.uploader.runner;

import static java.util.Comparator.comparing;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.Base64;
import java.util.Date;
import java.util.List;
import java.util.Objects;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.TimeUnit;
import java.util.stream.Stream;

import org.apache.commons.lang.Validate;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import com.drew.imaging.ImageMetadataReader;
import com.drew.metadata.Directory;
import com.drew.metadata.Metadata;
import com.drew.metadata.exif.ExifSubIFDDirectory;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.photo.uploader.fetcher.PhotoFetcher;
import cz.lriedel.photo.uploader.model.Album;
import cz.lriedel.photo.uploader.model.args.UploadPhotosArgs;
import cz.lriedel.photo.uploader.model.request.AlbumPrototype;
import cz.lriedel.photo.uploader.model.request.PhotoPrototype;

@Component
public class UploadPhotosProcessor extends AbstractProcessor<UploadPhotosArgs> {

    private static final int AVAILABLE_WORKERS = 16;

    private static final String CREATE_ALBUM_ENDPOINT_PATTERN = "/api/places/%s/albums";
    private static final String REFRESH_ALBUM_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/refresh";
    private static final String CREATE_PHOTO_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/photos";

    private final PhotoFetcher photoFetcher;

    public UploadPhotosProcessor(RestTemplate restTemplate, ObjectMapper objectMapper, PhotoFetcher photoFetcher) {
        super(restTemplate, objectMapper, UploadPhotosArgs.class);
        this.photoFetcher = Objects.requireNonNull(photoFetcher);
    }

    @Override
    protected void process(UploadPhotosArgs args) throws IOException, InterruptedException {
        long albumId = tryCreateAlbum(args);
        uploadPhotos(args, albumId);
        refreshAlbum(args, albumId);
    }

    private long tryCreateAlbum(UploadPhotosArgs args) throws JsonProcessingException {
        Long albumId = args.albumId();
        if (albumId != null) {
            return albumId;
        }

        logger.info("Album for the place {} does not exist. Creating a new album...", args.placeId());
        AlbumPrototype albumPrototype = new AlbumPrototype(Objects.requireNonNull(args.timestamp(), "Timestamp is not set."));
        Album createdAlbum = restTemplate.postForObject(String.format(CREATE_ALBUM_ENDPOINT_PATTERN, args.placeId()),
            objectMapper.writeValueAsString(albumPrototype), Album.class);
        return Objects.requireNonNull(createdAlbum, "Album was not created.").id();
    }

    private void uploadPhotos(UploadPhotosArgs args, long albumId) throws IOException, InterruptedException {
        String createPhotoUri = String.format(CREATE_PHOTO_ENDPOINT_PATTERN, args.placeId(), albumId);

        try (Stream<Path> paths = Files.list(args.path())) {
            List<Path> sortedPaths = paths.sorted(comparing(UploadPhotosProcessor::getPhotoCreationTime)).toList();

            ExecutorService executorService = Executors.newFixedThreadPool(AVAILABLE_WORKERS);
            for (int i = 0; i < sortedPaths.size(); ++i) {
                final int position = i + 1;
                executorService.submit(() -> uploadPhoto(sortedPaths.get(position - 1), position, createPhotoUri));
            }

            executorService.shutdown();
            Validate.isTrue(executorService.awaitTermination(Long.MAX_VALUE, TimeUnit.DAYS));
        }
    }

    private Album refreshAlbum(UploadPhotosArgs args, long albumId) {
        logger.info("Uploading has finished. Refreshing the album...");
        String url = String.format(REFRESH_ALBUM_ENDPOINT_PATTERN, args.placeId(), albumId);
        if (args.mainPhotoPosition() != null) {
            url += "?mainPhotoPosition=" + args.mainPhotoPosition();
        }
        return restTemplate.postForObject(url, null, Album.class);
    }

    private void uploadPhoto(Path path, int position, String uri) {
        logger.info("Uploading '{}'...", path);

        try {
            PhotoPrototype photoPrototype = new PhotoPrototype(path.getFileName().toString(), position,
                Base64.getEncoder().encodeToString(photoFetcher.fetch(path)));
            restTemplate.postForObject(uri, objectMapper.writeValueAsString(photoPrototype), Void.class);
        } catch (Exception e) {
            logger.error("Error occurred when uploading a photo.", e);
        }
    }

    private static Date getPhotoCreationTime(Path path) {
        try {
            Metadata metadata = ImageMetadataReader.readMetadata(path.toFile());
            for (Directory directory : metadata.getDirectories()) {
                if (directory.containsTag(ExifSubIFDDirectory.TAG_DATETIME_ORIGINAL)) {
                    return directory.getDate(ExifSubIFDDirectory.TAG_DATETIME_ORIGINAL);
                }
            }
        } catch (Exception e) {
            // Do nothing.
        }

        throw new IllegalStateException("Could not obtain creation date for '" + path + "'.");
    }
}
