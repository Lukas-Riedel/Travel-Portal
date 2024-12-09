package cz.lriedel.photo.uploader.processor;

import static java.util.Comparator.comparing;

import java.io.IOException;
import java.nio.channels.FileChannel;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.ArrayList;
import java.util.Base64;
import java.util.Date;
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
import java.util.stream.Collectors;
import java.util.stream.Stream;

import org.apache.commons.lang.Validate;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import com.drew.imaging.ImageMetadataReader;
import com.drew.metadata.Directory;
import com.drew.metadata.Metadata;
import com.drew.metadata.exif.ExifSubIFDDirectory;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.photo.uploader.HttpEntityProvider;
import cz.lriedel.photo.uploader.fetcher.PhotoFetcher;
import cz.lriedel.photo.uploader.model.Album;
import cz.lriedel.photo.uploader.model.args.UploadPhotosArgs;
import cz.lriedel.photo.uploader.model.request.AlbumPrototype;
import cz.lriedel.photo.uploader.model.request.PhotoPrototype;

@Component
class UploadPhotosProcessor extends AbstractProcessor<UploadPhotosArgs> {

    private static final int AVAILABLE_WORKERS = 16;

    private static final String CREATE_ALBUM_ENDPOINT_PATTERN = "/api/places/%s/albums";
    private static final String REFRESH_ALBUM_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/refresh";
    private static final String CREATE_PHOTO_ENDPOINT_PATTERN = "/api/places/%s/albums/%s/photos";

    private static final String JPG_SUFFIX = ".jpg";

    private final PhotoFetcher photoFetcher;

    public UploadPhotosProcessor(RestTemplate restTemplate, RetryTemplate retryTemplate, ObjectMapper objectMapper,
                                 HttpEntityProvider httpEntityProvider, PhotoFetcher photoFetcher) {
        super(restTemplate, retryTemplate, objectMapper, httpEntityProvider, UploadPhotosArgs.class);
        this.photoFetcher = Objects.requireNonNull(photoFetcher);
    }

    @Override
    public void process(UploadPhotosArgs args) throws IOException, InterruptedException, ExecutionException {
        long albumId = tryCreateAlbum(args);
        uploadPhotos(args, albumId);
        refreshAlbum(args, albumId);
    }

    private long tryCreateAlbum(UploadPhotosArgs args) throws JsonProcessingException {
        Long albumId = args.albumId();
        if (albumId != null) {
            return albumId;
        }

        logger.info("Album for place {} does not exist. Creating a new album...", args.placeId());
        AlbumPrototype albumPrototype = new AlbumPrototype(Objects.requireNonNull(args.timestamp(), "Timestamp is not set."));
        Album createdAlbum = restTemplate.postForObject(String.format(CREATE_ALBUM_ENDPOINT_PATTERN, args.placeId()),
                httpEntityProvider.getHttpEntity(albumPrototype), Album.class);
        return Objects.requireNonNull(createdAlbum, "Album was not created.").id();
    }

    private void uploadPhotos(UploadPhotosArgs args, long albumId) throws IOException, InterruptedException, ExecutionException {
        String createPhotoUri = String.format(CREATE_PHOTO_ENDPOINT_PATTERN, args.placeId(), albumId);

        try (Stream<Path> paths = Files.list(args.path())) {
            ExecutorService executorService = Executors.newFixedThreadPool(AVAILABLE_WORKERS);
            Queue<Path> queue =
                paths.sorted(comparing(UploadPhotosProcessor::getPhotoCreationTime)).collect(Collectors.toCollection(LinkedList::new));

            int currentParallelRequestsCount = 1;
            int position = 1;

            while (!queue.isEmpty()) {
                List<Future<Double>> futures = new ArrayList<>();
                for (int i = 0; i < currentParallelRequestsCount && !queue.isEmpty(); ++i) {
                    final Path submittedPath = queue.remove();
                    final int submittedPosition = position++;

                    futures.add(executorService.submit(() -> uploadPhoto(submittedPath, submittedPosition, createPhotoUri)));
                }

                double sum = 0;
                for (Future<Double> future : futures) {
                    sum += future.get();
                }
                double averageProcessingSpeed = sum / futures.size();
                currentParallelRequestsCount = Math.min(AVAILABLE_WORKERS, (int) Math.ceil(averageProcessingSpeed));

                logger.info("Totally {}/{} photos were uploaded.", position - 1, position - 1 + queue.size());
            }

            executorService.shutdown();
            Validate.isTrue(executorService.awaitTermination(Long.MAX_VALUE, TimeUnit.DAYS));
        }
    }

    private Album refreshAlbum(UploadPhotosArgs args, long albumId) throws JsonProcessingException {
        logger.info("Uploading has finished. Refreshing the album...");
        String url = String.format(REFRESH_ALBUM_ENDPOINT_PATTERN, args.placeId(), albumId)
                + (args.mainPhotoPosition() == null ? "" : "?mainPhotoPosition=" + args.mainPhotoPosition());
        return retryTemplate.execute(context -> restTemplate.postForObject(url, httpEntityProvider.getEmptyHttpEntity(), Album.class));
    }

    private double uploadPhoto(Path path, int position, String uri) throws IOException {
        PhotoPrototype photoPrototype = new PhotoPrototype(UUID.randomUUID() + JPG_SUFFIX, position,
                Base64.getEncoder().encodeToString(photoFetcher.fetch(path)));
        long start = System.currentTimeMillis();
        retryTemplate.execute(context -> restTemplate.postForObject(uri, httpEntityProvider.getHttpEntity(photoPrototype), Void.class));
        long uploadDuration = (System.currentTimeMillis() - start) / 1000;
        double fileSize = FileChannel.open(path).size() / (1024.0 * 1024.0);
        return 8 * fileSize / uploadDuration;
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
