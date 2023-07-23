package cz.lriedel.photo.uploader.rest;

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
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.CrossOrigin;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.ResponseBody;
import org.springframework.web.client.RestTemplate;

import com.drew.imaging.ImageMetadataReader;
import com.drew.metadata.Directory;
import com.drew.metadata.Metadata;
import com.drew.metadata.exif.ExifSubIFDDirectory;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.photo.uploader.fetcher.PhotoFetcher;
import cz.lriedel.photo.uploader.model.Album;
import cz.lriedel.photo.uploader.model.request.AlbumPrototype;
import cz.lriedel.photo.uploader.model.request.PhotoPrototype;
import cz.lriedel.photo.uploader.model.request.UploadPrototype;

@Controller
@RequestMapping("photos")
public class PhotosController {

    private static final Logger LOGGER = LoggerFactory.getLogger(PhotosController.class);

    private static final int AVAILABLE_WORKERS = 16;

    private static final String CREATE_ALBUM_ENDPOINT_PATTERN = "/api/places/%s/albums";
    private static final String REFRESH_ALBUM_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/refresh";
    private static final String CREATE_PHOTO_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/photos";

    private final RestTemplate restTemplate;
    private final ObjectMapper objectMapper;
    private final PhotoFetcher photoFetcher;

    public PhotosController(RestTemplate restTemplate, ObjectMapper objectMapper, PhotoFetcher photoFetcher) {
        this.restTemplate = restTemplate;
        this.objectMapper = objectMapper;
        this.photoFetcher = photoFetcher;
    }

    @PostMapping("/upload")
    @CrossOrigin(origins = "${service.url}")
    @ResponseBody
    public Album uploadPhotos(UploadPrototype uploadPrototype) throws IOException, InterruptedException {
        LOGGER.info("Received request to upload photos: {}", uploadPrototype);

        long albumId = tryCreateAlbum(uploadPrototype);
        uploadPhotos(uploadPrototype, albumId);
        return refreshAlbum(uploadPrototype, albumId);
    }

    private long tryCreateAlbum(UploadPrototype uploadPrototype) throws JsonProcessingException {
        Long albumId = uploadPrototype.albumId();
        if (albumId != null) {
            return albumId;
        }

        LOGGER.info("Album for the place '{}' does not exist. Creating a new album...", uploadPrototype.placeId());
        AlbumPrototype albumPrototype = new AlbumPrototype(Objects.requireNonNull(uploadPrototype.timestamp(), "Timestamp is not set."));
        Album createdAlbum = restTemplate.postForObject(String.format(CREATE_ALBUM_ENDPOINT_PATTERN, uploadPrototype.placeId()),
            objectMapper.writeValueAsString(albumPrototype), Album.class);
        return Objects.requireNonNull(createdAlbum, "Album was not created.").id();
    }

    private void uploadPhotos(UploadPrototype uploadPrototype, long albumId) throws IOException, InterruptedException {
        String createPhotoUri = String.format(CREATE_PHOTO_ENDPOINT_PATTERN, uploadPrototype.placeId(), albumId);

        try (Stream<Path> paths = Files.list(uploadPrototype.path())) {
            List<Path> sortedPaths = paths.sorted(comparing(PhotosController::getPhotoCreationTime)).toList();

            try (ExecutorService executorService = Executors.newFixedThreadPool(AVAILABLE_WORKERS)) {
                for (int i = 1; i <= sortedPaths.size(); ++i) {
                    final int position = i;
                    executorService.submit(() -> uploadPhoto(sortedPaths.get(position), position, createPhotoUri));
                }

                executorService.shutdown();
                Validate.isTrue(executorService.awaitTermination(Long.MAX_VALUE, TimeUnit.DAYS));
            }
        }
    }

    private Album refreshAlbum(UploadPrototype uploadPrototype, long albumId) {
        LOGGER.info("Uploading has finished. Refreshing the album...");
        return restTemplate.postForObject(String.format(REFRESH_ALBUM_ENDPOINT_PATTERN, uploadPrototype.placeId(), albumId),
            null, Album.class);
    }

    private void uploadPhoto(Path path, int position, String uri) {
        LOGGER.info("Uploading '{}'...", path);

        try {
            PhotoPrototype photoPrototype = new PhotoPrototype(path.getFileName().toString(), position,
                Base64.getEncoder().encodeToString(photoFetcher.fetch(path)));
            restTemplate.postForObject(uri, objectMapper.writeValueAsString(photoPrototype), Void.class);
        } catch (Exception e) {
            LOGGER.error("Error occurred when uploading a photo.", e);
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
