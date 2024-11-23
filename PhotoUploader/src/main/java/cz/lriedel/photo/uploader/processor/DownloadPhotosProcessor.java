package cz.lriedel.photo.uploader.processor;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.photo.uploader.HttpEntityProvider;
import cz.lriedel.photo.uploader.model.Album;
import cz.lriedel.photo.uploader.model.Date;
import cz.lriedel.photo.uploader.model.Photo;
import cz.lriedel.photo.uploader.model.Place;
import cz.lriedel.photo.uploader.model.args.DownloadPhotosArgs;
import cz.lriedel.photo.uploader.model.args.UploadPhotosArgs;
import cz.lriedel.photo.uploader.model.request.JobPrototype;
import org.apache.commons.io.FileUtils;
import org.apache.commons.lang.StringUtils;
import org.springframework.http.HttpMethod;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import java.io.IOException;
import java.net.URL;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.util.Arrays;
import java.util.Objects;
import java.util.UUID;

@Component
public class DownloadPhotosProcessor extends AbstractProcessor<DownloadPhotosArgs> {
    
    private static final String LIST_PLACES_ENDPOINT = "/api/places?maxEnd=" + System.currentTimeMillis() / 1000;
    private static final String LIST_PHOTOS_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/photos";
    private static final String SCHEDULE_JOB_ENDPOINT = "/api/jobs/schedule";

    private static final String BASE_URL_DOWNLOAD_SUFFIX = "=d";
    private static final String JPG_SUFFIX = ".jpg";

    public DownloadPhotosProcessor(RestTemplate restTemplate, RetryTemplate retryTemplate,
                                   ObjectMapper objectMapper, HttpEntityProvider httpEntityProvider) {
        super(restTemplate, retryTemplate, objectMapper, httpEntityProvider, DownloadPhotosArgs.class);
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

                Path albumPhotosDirectory = args.path().resolve(Long.toString(place.id()))
                        .resolve(Long.toString(date.start())).resolve(Integer.toString(mainPhotoPosition));
                FileUtils.deleteDirectory(albumPhotosDirectory.toFile());
                Files.createDirectories(albumPhotosDirectory);

                logger.info("Downloading album {}/{}...", ++i, albumsCount);
                downloadPhotos(albumPhotosDirectory, photos);
                schedulePhotosUploading(new UploadPhotosArgs(place.id(), date.start(), null, mainPhotoPosition, albumPhotosDirectory));
            }
        }
    }

    private Place[] getPlaces() {
        return restTemplate.exchange(LIST_PLACES_ENDPOINT, HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), Place[].class).getBody();
    }
    
    private Photo[] fetchPhotos(long placeId, long albumId) throws JsonProcessingException {
        return retryTemplate.execute(context -> restTemplate.exchange(String.format(LIST_PHOTOS_ENDPOINT_PATTERN, placeId, albumId),
                HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), Photo[].class).getBody());
    }

    private void downloadPhotos(Path photosDirectory, Photo[] photos) throws IOException {
        int i = 0;
        for (Photo photo : photos) {
            logger.info("Downloading photo {}/{}...", ++i, photos.length);
            Files.copy(new URL(photo.url() + BASE_URL_DOWNLOAD_SUFFIX).openStream(),
                photosDirectory.resolve(UUID.randomUUID() + JPG_SUFFIX), StandardCopyOption.REPLACE_EXISTING);
        }
    }

    private void schedulePhotosUploading(UploadPhotosArgs uploadPhotosArgs) throws JsonProcessingException {
        JobPrototype jobPrototype = new JobPrototype(StrictUploadPhotosProcessor.class.getSimpleName()
                .replace(Processor.class.getSimpleName(), StringUtils.EMPTY), uploadPhotosArgs);
        retryTemplate.execute(context -> restTemplate.postForObject(SCHEDULE_JOB_ENDPOINT,
                httpEntityProvider.getHttpEntity(jobPrototype), Void.class));
    }

    private int getMainPhotoPosition(Album album, Photo[] photos) {
        int i = 1;
        for (Photo photo : photos) {
            if (photo.id() == album.mainPhotoId()) {
                return i;
            }
            ++i;
        }
        
        logger.warn("The main photo position could not be obtained.");
        return 1;
    }
}
