package cz.lriedel.photo.uploader.processor;

import java.io.IOException;
import java.net.URL;
import java.nio.channels.FileChannel;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.LinkedList;
import java.util.List;
import java.util.Objects;
import java.util.Queue;
import java.util.UUID;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.Future;
import java.util.concurrent.TimeUnit;

import org.apache.commons.io.FileUtils;
import org.apache.commons.lang.StringUtils;
import org.apache.commons.lang.Validate;
import org.springframework.http.HttpMethod;
import org.springframework.retry.support.RetryTemplate;
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
import cz.lriedel.photo.uploader.model.args.UploadPhotosArgs;
import cz.lriedel.photo.uploader.model.request.JobPrototype;

@Component
public class DownloadPhotosProcessor extends AbstractProcessor<DownloadPhotosArgs> {

    private static final int AVAILABLE_WORKERS = 32;

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

    private void downloadPhotos(Path photosDirectory, Photo[] photos) throws IOException, InterruptedException, ExecutionException {
        ExecutorService executorService = Executors.newFixedThreadPool(AVAILABLE_WORKERS);
        Queue<Photo> queue = new LinkedList<>(Arrays.asList(photos));

        int currentParallelRequestsCount = 1;
        int position = 1;

        while (!queue.isEmpty()) {
            List<Future<Double>> futures = new ArrayList<>();
            for (int i = 0; i < currentParallelRequestsCount && !queue.isEmpty(); ++i) {
                final Photo submittedPhoto = queue.remove();
                futures.add(executorService.submit(() -> downloadPhoto(photosDirectory, submittedPhoto)));
                ++position;
            }

            double sum = 0;
            for (Future<Double> future : futures) {
                sum += future.get();
            }
            double averageProcessingSpeed = sum / futures.size();
            currentParallelRequestsCount = Math.min(AVAILABLE_WORKERS, (int) Math.ceil(averageProcessingSpeed));

            logger.info("Totally {}/{} photos were downloaded.", position - 1, position - 1 + queue.size());
        }

        executorService.shutdown();
        Validate.isTrue(executorService.awaitTermination(Long.MAX_VALUE, TimeUnit.DAYS));
    }

    private double downloadPhoto(Path photosDirectory, Photo photo) throws IOException {
        Path path = photosDirectory.resolve(UUID.randomUUID() + JPG_SUFFIX);
        long start = System.currentTimeMillis();
        Files.copy(new URL(photo.url() + BASE_URL_DOWNLOAD_SUFFIX).openStream(), path, StandardCopyOption.REPLACE_EXISTING);
        long downloadDuration = (System.currentTimeMillis() - start) / 1000;
        double fileSize = FileChannel.open(path).size() / (1024.0 * 1024.0);
        return 8 * fileSize / downloadDuration;
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
