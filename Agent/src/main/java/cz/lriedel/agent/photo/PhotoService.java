package cz.lriedel.agent.photo;

import com.drew.imaging.ImageMetadataReader;
import com.drew.metadata.Directory;
import com.drew.metadata.Metadata;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.AgentContextDataProvider;
import cz.lriedel.agent.client.CoreClient;
import cz.lriedel.agent.model.api.Album;
import cz.lriedel.agent.model.api.Place;
import cz.lriedel.agent.persistance.Configuration;
import cz.lriedel.agent.persistance.ConfigurationRepository;
import cz.lriedel.agent.persistance.UploadedPhoto;
import cz.lriedel.agent.persistance.UploadedPhotoRepository;
import cz.lriedel.agent.photo.fetcher.PhotoFetcher;
import lombok.SneakyThrows;
import lombok.Synchronized;
import lombok.extern.slf4j.Slf4j;
import org.apache.commons.lang3.Validate;
import org.springframework.lang.Nullable;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Service;

import java.nio.channels.FileChannel;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.Duration;
import java.time.Instant;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Date;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;
import java.util.Objects;
import java.util.Optional;
import java.util.Queue;
import java.util.Set;
import java.util.UUID;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.Future;
import java.util.concurrent.TimeUnit;
import java.util.function.Predicate;
import java.util.stream.Stream;

import static com.drew.metadata.exif.ExifDirectoryBase.TAG_DATETIME_ORIGINAL;
import static cz.lriedel.agent.persistance.ConfigurationRepository.SYNCHRONIZED_FOLDERS_CONFIGURATION_KEY;
import static java.util.Comparator.comparing;
import static java.util.stream.Collectors.toCollection;
import static java.util.stream.Collectors.toSet;

@Slf4j
@Service
public class PhotoService implements AgentContextDataProvider {

    private static final int AVAILABLE_WORKERS = 16;
    private static final Duration MIN_PHOTO_AGE = Duration.ofSeconds(10);
    private static final Duration UPLOADED_PHOTOS_RETENTION_POLICY = Duration.ofDays(365);
    private static final String JPG_SUFFIX = ".jpg";

    private final CoreClient coreClient;
    private final RetryTemplate retryTemplate;
    private final PhotoFetcher photoFetcher;
    private final ConfigurationRepository configurationRepository;
    private final UploadedPhotoRepository uploadedPhotoRepository;
    private final ObjectMapper objectMapper;

    public PhotoService(CoreClient coreClient, RetryTemplate retryTemplate, PhotoFetcher photoFetcher,
            ConfigurationRepository configurationRepository, UploadedPhotoRepository uploadedPhotoRepository, ObjectMapper objectMapper) {
        this.coreClient = coreClient;
        this.retryTemplate = retryTemplate;
        this.photoFetcher = photoFetcher;
        this.configurationRepository = configurationRepository;
        this.uploadedPhotoRepository = uploadedPhotoRepository;
        this.objectMapper = objectMapper;
    }

    @SneakyThrows
    private static Date getPhotoCreationTime(Path path) {
        Metadata metadata = ImageMetadataReader.readMetadata(path.toFile());
        for (Directory directory : metadata.getDirectories()) {
            if (directory.containsTag(TAG_DATETIME_ORIGINAL)) {
                return directory.getDate(TAG_DATETIME_ORIGINAL);
            }
        }

        throw new IllegalStateException("Could not obtain creation date for '" + path + "'.");
    }

    private static String getPhotoName() {
        return UUID.randomUUID() + JPG_SUFFIX;
    }

    private static String getExpectedAlbumName(String placeName, Instant start) {
        return String.join(" ", placeName, start.atZone(ZoneId.systemDefault()).format(DateTimeFormatter.ofPattern("d.M.yyyy")));
    }

    @Scheduled(fixedDelayString = "${agent.photo.synchronization.interval}", timeUnit = TimeUnit.SECONDS)
    public void synchronizeFolders() {
        List<SynchronizedFolder> synchronizedFolders = getAndUpdateNonExpiredSynchronizedFolders();

        if (!synchronizedFolders.isEmpty()) {
            Set<String> uploadedPaths = uploadedPhotoRepository.findAll().stream().map(UploadedPhoto::getPath).collect(toSet());

            for (Place place : coreClient.getPlaces()) {
                for (cz.lriedel.agent.model.api.Date date : place.getDates()) {
                    String expectedAlbumName = getExpectedAlbumName(place.getName(), Instant.ofEpochSecond(date.getStart()));

                    for (SynchronizedFolder synchronizedFolder : synchronizedFolders) {
                        Path albumFolder = getAlbumFolder(synchronizedFolder, expectedAlbumName);

                        if (albumFolder != null) {
                            log.info("Synchronizing '{}'...", albumFolder);
                            Album album = date.getAlbum();

                            if (album == null) {
                                album = coreClient.createAlbum(place.getId(), Instant.ofEpochSecond(date.getStart()));
                            }

                            boolean anyUploaded = uploadPhotos(place.getId(), album.getId(), albumFolder,
                                    path -> !uploadedPaths.contains(path.toString()) && isPathCreated(path));
                            if (anyUploaded) {
                                String albumId = album.getId();
                                retryTemplate.execute(context -> {
                                    coreClient.refreshAlbum(place.getId(), albumId, null);
                                    return null;
                                });
                            }
                        }
                    }
                }
            }
        }
    }

    @Synchronized
    @SneakyThrows
    public void requestFolderSynchronization(Path path, Instant expiration) {
        List<SynchronizedFolder> synchronizedFolders = new ArrayList<>(getAndUpdateNonExpiredSynchronizedFolders());
        synchronizedFolders.removeIf(folder -> folder.path().equals(path));
        synchronizedFolders.add(new SynchronizedFolder(path, expiration));
        saveSynchronizedFolders(synchronizedFolders);
    }

    public void replacePhoto(String placeId, String albumId, String replacedPhotoId, Path path) {
        log.info("Uploading a replacement for the photo {}...", replacedPhotoId);
        byte[] data = photoFetcher.fetch(path);
        retryTemplate.execute(context -> {
            coreClient.uploadPhoto(placeId, albumId, getPhotoName(), replacedPhotoId, data);
            return null;
        });

        log.info("Uploading of the replacement has finished. Refreshing the album...");
        retryTemplate.execute(context -> {
            coreClient.refreshAlbum(placeId, albumId, null);
            return null;
        });
    }

    public void uploadPhotos(String placeId, @Nullable Instant timestamp, @Nullable String albumId, @Nullable Integer mainPhotoPosition, Path path) {
        if (albumId == null) {
            log.info("Album for place {} does not exist. Creating a new album...", placeId);
            albumId = coreClient.createAlbum(placeId, Objects.requireNonNull(timestamp)).getId();
        }

        log.info("Starting photos uploading for album {}...", albumId);
        uploadPhotos(placeId, albumId, path, whatever -> true);

        log.info("Uploading has finished. Refreshing the album...");
        String effectiveAlbumId = albumId;
        retryTemplate.execute(context -> {
            coreClient.refreshAlbum(placeId, effectiveAlbumId, mainPhotoPosition);
            return null;
        });
    }

    @SneakyThrows
    private boolean uploadPhotos(String placeId, String albumId, Path path, Predicate<Path> pathFiter) {
        try (Stream<Path> paths = Files.list(path)) {
            return uploadPhotos(placeId, albumId, paths.filter(pathFiter));
        }
        finally {
            uploadedPhotoRepository.deleteByUploadedBefore(Instant.now().minus(UPLOADED_PHOTOS_RETENTION_POLICY));
        }
    }

    @SneakyThrows
    private boolean uploadPhotos(String placeId, String albumId, Stream<Path> paths) {
        ExecutorService executorService = Executors.newFixedThreadPool(AVAILABLE_WORKERS);
        Queue<Path> queue = paths.sorted(comparing(PhotoService::getPhotoCreationTime)).collect(toCollection(LinkedList::new));

        int expectedBatchSize = queue.size();
        String batchId = UUID.randomUUID().toString();

        int currentParallelRequestsCount = 1;
        int position = 1;

        while (!queue.isEmpty()) {
            List<Future<Double>> futures = new ArrayList<>();
            for (int i = 0; i < currentParallelRequestsCount && !queue.isEmpty(); ++i) {
                Path submittedPath = queue.remove();
                int submittedPosition = position++;

                futures.add(
                    executorService.submit(() -> uploadPhoto(placeId, albumId, batchId, expectedBatchSize, submittedPosition, submittedPath)));
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

        return position > 1;
    }

    @SneakyThrows
    private double uploadPhoto(String placeId, String albumId, String batchId, int expectedBatchSize, int batchPosition, Path path) {
        long start = System.currentTimeMillis();
        byte[] data = photoFetcher.fetch(path);
        retryTemplate.execute(context -> {
            coreClient.uploadPhoto(placeId, albumId, getPhotoName(), batchId, expectedBatchSize, batchPosition, data);
            return null;
        });
        long uploadDuration = (System.currentTimeMillis() - start) / 1000;
        double fileSize = FileChannel.open(path).size() / (1024.0 * 1024.0);
        uploadedPhotoRepository.save(new UploadedPhoto(path.toString(), Instant.now()));
        return 8 * fileSize / uploadDuration;
    }

    @SneakyThrows
    private List<SynchronizedFolder> getSynchronizedFolders() {
        Optional<Configuration> configuration = configurationRepository.findById(SYNCHRONIZED_FOLDERS_CONFIGURATION_KEY);
        if (configuration.isEmpty()) {
            return List.of();
        }

        return objectMapper.readValue(configuration.get().getValue(), new TypeReference<>() {

        });
    }

    @Synchronized
    private List<SynchronizedFolder> getAndUpdateNonExpiredSynchronizedFolders() {
        List<SynchronizedFolder> nonExpiredSynchronizedFolders = getSynchronizedFolders().stream()
                .filter(folder -> folder.expiration().isAfter(Instant.now())).toList();
        saveSynchronizedFolders(nonExpiredSynchronizedFolders);
        return nonExpiredSynchronizedFolders;
    }

    @SneakyThrows
    private void saveSynchronizedFolders(List<SynchronizedFolder> synchronizedFolders) {
        configurationRepository.save(new Configuration(SYNCHRONIZED_FOLDERS_CONFIGURATION_KEY, objectMapper.writeValueAsString(synchronizedFolders)));
    }

    @Override
    public Map<String, Object> getContextData() {
        return Map.of(SYNCHRONIZED_FOLDERS_CONFIGURATION_KEY, getAndUpdateNonExpiredSynchronizedFolders());
    }

    @SneakyThrows
    @Nullable
    private Path getAlbumFolder(SynchronizedFolder synchronizedFolder, String expectedAlbumName) {
        Path expectedPath = synchronizedFolder.path().resolve(expectedAlbumName);
        return Files.isDirectory(expectedPath) ? expectedPath : null;
    }

    private boolean isPathCreated(Path path) {
        try {
            return Duration.between(Files.getLastModifiedTime(path).toInstant(), Instant.now()).compareTo(MIN_PHOTO_AGE) > 0;
        }
        catch (Exception e) {
            return false;
        }
    }
}
