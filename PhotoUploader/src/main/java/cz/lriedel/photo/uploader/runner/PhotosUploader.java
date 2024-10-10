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
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.boot.CommandLineRunner;
import org.springframework.stereotype.Controller;
import org.springframework.web.client.RestTemplate;

import com.drew.imaging.ImageMetadataReader;
import com.drew.metadata.Directory;
import com.drew.metadata.Metadata;
import com.drew.metadata.exif.ExifSubIFDDirectory;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.photo.uploader.fetcher.PhotoFetcher;
import cz.lriedel.photo.uploader.model.Album;
import cz.lriedel.photo.uploader.model.UploadPhotosJob;
import cz.lriedel.photo.uploader.model.request.AlbumPrototype;
import cz.lriedel.photo.uploader.model.request.PhotoPrototype;
import cz.lriedel.photo.uploader.model.UploadPhotosJobArgs;

@Controller
public class PhotosUploader implements CommandLineRunner {

    private static final Logger LOGGER = LoggerFactory.getLogger(PhotosUploader.class);

    private static final int RETRY_REQUEST_TIME = 10000;
    private static final int AVAILABLE_WORKERS = 16;

    private static final String GET_JOBS_ENDPOINT = "/api/jobs/UploadPhotos";
    private static final String DELETE_JOB_ENDPOINT = "/api/jobs/%s";

    private static final String CREATE_ALBUM_ENDPOINT_PATTERN = "/api/places/%s/albums";
    private static final String REFRESH_ALBUM_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/refresh";
    private static final String CREATE_PHOTO_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/photos";

    private final RestTemplate restTemplate;
    private final ObjectMapper objectMapper;
    private final PhotoFetcher photoFetcher;

    public PhotosUploader(RestTemplate restTemplate, ObjectMapper objectMapper, PhotoFetcher photoFetcher) {
        this.restTemplate = restTemplate;
        this.objectMapper = objectMapper;
        this.photoFetcher = photoFetcher;
    }

    @Override
    public void run(String... args) throws Exception {
        while (true) {
            UploadPhotosJob[] uploadPhotosJobs = restTemplate.getForObject(GET_JOBS_ENDPOINT, UploadPhotosJob[].class);

            if (uploadPhotosJobs != null) {
                for (UploadPhotosJob uploadPhotosJob : uploadPhotosJobs) {
                    try {
                        uploadPhotos(uploadPhotosJob.args());
                    }
                    catch (Throwable e) {
                        LOGGER.error("Unknown error occurred when processing '{}'.", uploadPhotosJob, e);
                    }
                    restTemplate.delete(String.format(DELETE_JOB_ENDPOINT, uploadPhotosJob.id()));
                }
            }

            Thread.sleep(RETRY_REQUEST_TIME);
        }
    }

    private void uploadPhotos(UploadPhotosJobArgs uploadPhotosJobArgs) throws IOException, InterruptedException {
        LOGGER.info("Received request to upload photos: {}", uploadPhotosJobArgs);

        Validate.isTrue(uploadPhotosJobArgs.placeId() > 0, "Invalid place identifier.");
        Validate.isTrue(uploadPhotosJobArgs.timestamp() != null || uploadPhotosJobArgs.albumId() != null,
            "Either timestamp or album identifier must be set.");
        Validate.isTrue(uploadPhotosJobArgs.timestamp() == null || uploadPhotosJobArgs.albumId() == null,
            "Either timestamp or album identifier must be set, but not both.");
        Validate.isTrue(uploadPhotosJobArgs.mainPhotoPosition() == null || uploadPhotosJobArgs.mainPhotoPosition() > 0,
            "The main photo position must be either a positive number, or not set.");
        Objects.requireNonNull(uploadPhotosJobArgs.path(), "The path must be set.");
        Validate.isTrue(uploadPhotosJobArgs.path().toFile().exists(), "The directory does not exist.");

        long albumId = tryCreateAlbum(uploadPhotosJobArgs);
        uploadPhotos(uploadPhotosJobArgs, albumId);
        Album album = refreshAlbum(uploadPhotosJobArgs, albumId);

        if (album != null) {
            new ProcessBuilder("start", "\"\"", album.permalink()).start();
        }
    }

    private long tryCreateAlbum(UploadPhotosJobArgs uploadPhotosJobArgs) throws JsonProcessingException {
        Long albumId = uploadPhotosJobArgs.albumId();
        if (albumId != null) {
            return albumId;
        }

        LOGGER.info("Album for the place '{}' does not exist. Creating a new album...", uploadPhotosJobArgs.placeId());
        AlbumPrototype albumPrototype = new AlbumPrototype(Objects.requireNonNull(uploadPhotosJobArgs.timestamp(), "Timestamp is not set."));
        Album createdAlbum = restTemplate.postForObject(String.format(CREATE_ALBUM_ENDPOINT_PATTERN, uploadPhotosJobArgs.placeId()),
            objectMapper.writeValueAsString(albumPrototype), Album.class);
        return Objects.requireNonNull(createdAlbum, "Album was not created.").id();
    }

    private void uploadPhotos(UploadPhotosJobArgs uploadPhotosJobArgs, long albumId) throws IOException, InterruptedException {
        String createPhotoUri = String.format(CREATE_PHOTO_ENDPOINT_PATTERN, uploadPhotosJobArgs.placeId(), albumId);

        try (Stream<Path> paths = Files.list(uploadPhotosJobArgs.path())) {
            List<Path> sortedPaths = paths.sorted(comparing(PhotosUploader::getPhotoCreationTime)).toList();

            ExecutorService executorService = Executors.newFixedThreadPool(AVAILABLE_WORKERS);
            for (int i = 1; i <= sortedPaths.size(); ++i) {
                final int position = i;
                executorService.submit(() -> uploadPhoto(sortedPaths.get(position), position, createPhotoUri));
            }

            executorService.shutdown();
            Validate.isTrue(executorService.awaitTermination(Long.MAX_VALUE, TimeUnit.DAYS));
        }
    }

    private Album refreshAlbum(UploadPhotosJobArgs uploadPhotosJobArgs, long albumId) {
        LOGGER.info("Uploading has finished. Refreshing the album...");
        String url = String.format(REFRESH_ALBUM_ENDPOINT_PATTERN, uploadPhotosJobArgs.placeId(), albumId);
        if (uploadPhotosJobArgs.mainPhotoPosition() != null) {
            url += "?mainPhotoPosition=" + uploadPhotosJobArgs.mainPhotoPosition();
        }
        return restTemplate.postForObject(url, null, Album.class);
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
