package cz.lriedel.photo.uploader.processor;

import java.io.IOException;
import java.net.URL;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.util.Arrays;
import java.util.Objects;
import java.util.UUID;

import org.apache.commons.io.FileUtils;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpMethod;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.photo.uploader.HttpEntityProvider;
import cz.lriedel.photo.uploader.model.Album;
import cz.lriedel.photo.uploader.model.Date;
import cz.lriedel.photo.uploader.model.Photo;
import cz.lriedel.photo.uploader.model.Place;
import cz.lriedel.photo.uploader.model.args.DownloadPhotosArgs;

@Component
public class DownloadPhotosProcessor extends AbstractProcessor<DownloadPhotosArgs> {
    
    private static final String LIST_PLACES_ENDPOINT = "/api/places?maxEnd=" + System.currentTimeMillis() / 1000;
    private static final String LIST_PHOTOS_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/photos";
    private static final String SCHEDULE_JOB_ENDPOINT = "/api/jobs/schedule";

    private static final String BASE_URL_DOWNLOAD_SUFFIX = "=d";
    private static final String JPG_SUFFIX = ".jpg";

    private final Path photosRootDirectory;

    public DownloadPhotosProcessor(RestTemplate restTemplate, ObjectMapper objectMapper,
        HttpEntityProvider httpEntityProvider, @Value("${temp.dir.photos}") Path photosRootDirectory) {
        super(restTemplate, objectMapper, httpEntityProvider, DownloadPhotosArgs.class);
        this.photosRootDirectory = Objects.requireNonNull(photosRootDirectory);
    }

    @Override
    public void process(DownloadPhotosArgs args) throws Exception {
        Place[] places = getPlaces();
        long albumsCount = Arrays.stream(places)
            .map(Place::dates)
            .flatMap(Arrays::stream)
            .map(Date::album)
            .filter(Objects::nonNull)
            .count();

        int i = 0;
        for (Place place : places) {
            for (Date date : place.dates()) {
                if (date.album() == null) {
                    continue;
                }

                Photo[] photos = fetchPhotos(place.id(), date.album().id());
                int mainPhotoPosition = getMainPhotoPosition(date.album(), photos);

                Path albumPhotosDirectory = photosRootDirectory.resolve(Long.toString(place.id())).resolve(Long.toString(date.start())).resolve(Integer.toString(mainPhotoPosition));
                FileUtils.deleteDirectory(albumPhotosDirectory.toFile());
                Files.createDirectories(albumPhotosDirectory);

                logger.info("Downloading album {}/{}...", ++i, albumsCount);
                downloadPhotos(albumPhotosDirectory, photos);
            }
        }
    }

    private Place[] getPlaces() throws JsonProcessingException {
        return restTemplate.exchange(LIST_PLACES_ENDPOINT, HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), Place[].class).getBody();
    }
    
    private Photo[] fetchPhotos(long placeId, long albumId) throws JsonProcessingException {
        return restTemplate.exchange(String.format(LIST_PHOTOS_ENDPOINT_PATTERN, placeId, albumId),
                HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), Photo[].class).getBody();
    }

    private void downloadPhotos(Path photosDirectory, Photo[] photos) throws IOException {
        int i = 0;
        for (Photo photo : photos) {
            logger.info("Downloading photo {}/{}...", ++i, photos.length);
            Files.copy(new URL(photo.url() + BASE_URL_DOWNLOAD_SUFFIX).openStream(),
                photosDirectory.resolve(UUID.randomUUID() + JPG_SUFFIX), StandardCopyOption.REPLACE_EXISTING);
        }
    }

    private int getMainPhotoPosition(Album album, Photo[] photos) throws JsonProcessingException {
        int i = 1;
        for (Photo photo : photos) {
            if (photo.id() == album.mainPhotoId()) {
                return i;
            }
            ++i;
        }
        
        logger.warn("The main photo position could not be ontained.");
        return 1;
    }
}
