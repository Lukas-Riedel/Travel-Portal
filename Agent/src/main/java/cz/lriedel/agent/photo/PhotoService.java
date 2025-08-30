package cz.lriedel.agent.photo;

import static java.util.Comparator.comparing;

import java.nio.channels.FileChannel;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.ArrayList;
import java.util.Date;
import java.util.LinkedList;
import java.util.List;
import java.util.Objects;
import java.util.Queue;
import java.util.UUID;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.Future;
import java.util.concurrent.TimeUnit;
import java.util.stream.Collectors;
import java.util.stream.Stream;

import org.apache.commons.lang3.Validate;
import org.springframework.lang.Nullable;
import org.springframework.stereotype.Service;

import com.drew.imaging.ImageMetadataReader;
import com.drew.metadata.Directory;
import com.drew.metadata.Metadata;
import com.drew.metadata.exif.ExifSubIFDDirectory;

import cz.lriedel.agent.client.ServiceClient;
import cz.lriedel.agent.photo.fetcher.PhotoFetcher;
import lombok.SneakyThrows;
import lombok.extern.slf4j.Slf4j;

@Slf4j
@Service
public final class PhotoService {

    private static final int AVAILABLE_WORKERS = 16;
    private static final String JPG_SUFFIX = ".jpg";
    
    private final ServiceClient serviceClient;
    private final PhotoFetcher photoFetcher;
    
    public PhotoService(ServiceClient serviceClient, PhotoFetcher photoFetcher) {
        this.serviceClient = serviceClient;
        this.photoFetcher = photoFetcher;
    }

    public void replacePhoto(String placeId, String albumId, String replacedPhotoId, Path path) {
        log.info("Uploading a replacement for the photo {}...", replacedPhotoId);
        serviceClient.uploadPhoto(placeId, albumId, getPhotoName(path),
                replacedPhotoId, photoFetcher.fetch(path));
        log.info("Uploading of the replacement has finished. Refreshing the album...");
        serviceClient.refreshAlbum(placeId, albumId);
    }
    
    public void uploadPhotos(String placeId, @Nullable Long timestamp, @Nullable String albumId,
                             @Nullable Integer mainPhotoPosition, Path path) {
        if (albumId == null) {
            log.info("Album for place {} does not exist. Creating a new album...", placeId);
            albumId = serviceClient.createAlbum(placeId, Objects.requireNonNull(timestamp)).id();
        }
        log.info("Starting photos uploading for album {}...", albumId);
        uploadPhotos(placeId, albumId, path);
        log.info("Uploading has finished. Refreshing the album...");
        if (mainPhotoPosition != null) {
            serviceClient.refreshAlbum(placeId, albumId, mainPhotoPosition);
        }
        else {
            serviceClient.refreshAlbum(placeId, albumId);
        }
    }

    @SneakyThrows
    private void uploadPhotos(String placeId, String albumId, Path path) {
        try (Stream<Path> paths = Files.list(path)) {
            ExecutorService executorService = Executors.newFixedThreadPool(AVAILABLE_WORKERS);
            Queue<Path> queue = paths.sorted(comparing(PhotoService::getPhotoCreationTime))
                    .collect(Collectors.toCollection(LinkedList::new));

            int expectedBatchSize = queue.size();
            String batchId = UUID.randomUUID().toString();

            int currentParallelRequestsCount = 1;
            int position = 1;

            while (!queue.isEmpty()) {
                List<Future<Double>> futures = new ArrayList<>();
                for (int i = 0; i < currentParallelRequestsCount && !queue.isEmpty(); ++i) {
                    final Path submittedPath = queue.remove();
                    final int submittedPosition = position++;

                    futures.add(executorService.submit(() -> uploadPhoto(placeId, albumId, batchId, expectedBatchSize, submittedPosition, submittedPath)));
                }

                double sum = 0;
                for (Future<Double> future : futures) {
                    sum += future.get();
                }
                double averageProcessingSpeed = sum / futures.size();
                currentParallelRequestsCount = Math.min(AVAILABLE_WORKERS, (int) Math.ceil(averageProcessingSpeed));

                log.info("Totally {}/{} photos were uploaded.", position - 1, position - 1 + queue.size());
            }

            executorService.shutdown();
            Validate.isTrue(executorService.awaitTermination(Long.MAX_VALUE, TimeUnit.DAYS));
        }
    }

    @SneakyThrows
    private double uploadPhoto(String placeId, String albumId, String batchId, int expectedBatchSize, int batchPosition, Path path) {
        long start = System.currentTimeMillis();
        serviceClient.uploadPhoto(placeId, albumId, getPhotoName(path), batchId, expectedBatchSize, batchPosition, photoFetcher.fetch(path));
        long uploadDuration = (System.currentTimeMillis() - start) / 1000;
        double fileSize = FileChannel.open(path).size() / (1024.0 * 1024.0);
        return 8 * fileSize / uploadDuration;
    }

    @SneakyThrows
    private static Date getPhotoCreationTime(Path path) {
        Metadata metadata = ImageMetadataReader.readMetadata(path.toFile());
        for (Directory directory : metadata.getDirectories()) {
            if (directory.containsTag(ExifSubIFDDirectory.TAG_DATETIME_ORIGINAL)) {
                return directory.getDate(ExifSubIFDDirectory.TAG_DATETIME_ORIGINAL);
            }
        }
        throw new IllegalStateException("Could not obtain creation date for '" + path + "'.");
    }
    
    private static String getPhotoName(Path path) {
        return UUID.randomUUID() + JPG_SUFFIX;
    }
}
