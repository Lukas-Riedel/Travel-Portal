package cz.lriedel.agent.photo;

import com.drew.imaging.ImageMetadataReader;
import com.drew.metadata.Directory;
import com.drew.metadata.Metadata;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.AgentContextDataProvider;
import cz.lriedel.agent.client.CoreClient;
import cz.lriedel.agent.model.api.Album;
import cz.lriedel.agent.model.api.PendingPhoto;
import cz.lriedel.agent.model.api.Place;
import cz.lriedel.agent.persistance.Configuration;
import cz.lriedel.agent.persistance.ConfigurationRepository;
import cz.lriedel.agent.persistance.UploadedPhoto;
import cz.lriedel.agent.persistance.UploadedPhotoRepository;
import cz.lriedel.agent.photo.fetcher.PhotoFetcher;
import lombok.SneakyThrows;
import lombok.Synchronized;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.lang.Nullable;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Service;

import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.Duration;
import java.time.Instant;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.Base64;
import java.util.Date;
import java.util.HashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;
import java.util.Objects;
import java.util.Optional;
import java.util.Queue;
import java.util.Set;
import java.util.UUID;
import java.util.concurrent.CompletableFuture;
import java.util.concurrent.ConcurrentHashMap;
import java.util.concurrent.ExecutionException;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Future;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.TimeoutException;
import java.util.function.Predicate;
import java.util.stream.Stream;

import static com.drew.metadata.exif.ExifDirectoryBase.TAG_DATETIME_ORIGINAL;
import static cz.lriedel.agent.persistance.ConfigurationRepository.SYNCHRONIZED_FOLDERS_CONFIGURATION_KEY;
import static cz.lriedel.agent.persistance.ConfigurationRepository.DEFAULT_PHOTO_FOLDER_CONFIGURATION_KEY;
import static java.util.Comparator.comparing;
import static java.util.stream.Collectors.toCollection;
import static java.util.stream.Collectors.toSet;

@Slf4j
@Service
public class PhotoService implements AgentContextDataProvider {

    private static final Duration MIN_PHOTO_AGE = Duration.ofSeconds(10);
    private static final Duration UPLOADED_PHOTOS_RETENTION_POLICY = Duration.ofDays(365);
    private static final Duration ASYNC_UPLOADING_TIMEOUT = Duration.ofHours(1);
    private static final String JPG_SUFFIX = ".jpg";

    private static final String PHOTO_UPLOADING_TRIGGERED_EVENT_NAME = "PhotoUploadingTriggered";

    private final CoreClient coreClient;
    private final RetryTemplate retryTemplate;
    private final PhotoFetcher photoFetcher;
    private final ConfigurationRepository configurationRepository;
    private final UploadedPhotoRepository uploadedPhotoRepository;
    private final ObjectMapper objectMapper;
    private final ExecutorService executorService;

    private final String agentIdentifier;
    private final int availableWorkers;
    private final boolean asyncUploadingEnabled;
    private final Path defaultPhotoFolder;

    private final Map<String, CompletableFuture<Void>> pendingAsyncUploads = new ConcurrentHashMap<>();

    public PhotoService(CoreClient coreClient, RetryTemplate retryTemplate, PhotoFetcher photoFetcher,
            ConfigurationRepository configurationRepository, UploadedPhotoRepository uploadedPhotoRepository,
            ObjectMapper objectMapper, ExecutorService executorService, String agentIdentifier,
            @Value("${agent.core.workers}") int availableWorkers,
            @Value("${agent.photo.uploading.asynchronous}") boolean asyncUploadingEnabled,
            @Value("${agent.photo.folder.default:}") Path defaultPhotoFolder) {
        this.coreClient = coreClient;
        this.retryTemplate = retryTemplate;
        this.photoFetcher = photoFetcher;
        this.configurationRepository = configurationRepository;
        this.uploadedPhotoRepository = uploadedPhotoRepository;
        this.objectMapper = objectMapper;
        this.executorService = executorService;
        this.agentIdentifier = agentIdentifier;
        this.availableWorkers = availableWorkers;
        this.asyncUploadingEnabled = asyncUploadingEnabled;
        this.defaultPhotoFolder = defaultPhotoFolder;
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

                            String batchId = uploadPhotos(place.getId(), album.getId(), albumFolder,
                                    path -> !uploadedPaths.contains(path.toString().toLowerCase()) && isPathCreated(path), null);
                            if (!asyncUploadingEnabled && batchId != null) {
                                String albumId = album.getId();
                                retryTemplate.execute(context -> {
                                    coreClient.refreshAlbum(place.getId(), albumId, null, batchId);
                                    return null;
                                });
                            }
                        }
                    }
                }
            }
        }
    }

    public void completeUpload(String batchId) {
        CompletableFuture<Void> future = pendingAsyncUploads.remove(batchId);
        if (future != null) {
            future.complete(null);
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

        PendingPhoto pendingPhoto = coreClient.uploadPhoto(placeId, albumId, getPhotoName(), replacedPhotoId, data);

        log.info("Uploading of the replacement has finished. Refreshing the album...");
        retryTemplate.execute(context -> {
            coreClient.refreshAlbum(placeId, albumId, null, pendingPhoto.getBatchId());
            return null;
        });
    }

    public void uploadPhotos(String placeId, @Nullable Instant timestamp, @Nullable String albumId, @Nullable Integer mainPhotoPosition, Path path) {
        if (albumId == null) {
            log.info("Album for place {} does not exist. Creating a new album...", placeId);
            albumId = coreClient.createAlbum(placeId, Objects.requireNonNull(timestamp)).getId();
        }

        log.info("Starting photos uploading for album {}...", albumId);
        String batchId = uploadPhotos(placeId, albumId, path, whatever -> true, mainPhotoPosition);

        if (!asyncUploadingEnabled) {
            log.info("Uploading has finished. Refreshing the album...");
            String effectiveAlbumId = albumId;
            retryTemplate.execute(context -> {
                coreClient.refreshAlbum(placeId, effectiveAlbumId, mainPhotoPosition, batchId);
                return null;
            });
        }
    }

    @SneakyThrows
    private static Date getPhotoCreationTime(Path path) {
        try (InputStream inputStream = Files.newInputStream(path)) {
            Metadata metadata = ImageMetadataReader.readMetadata(inputStream);

            for (Directory directory : metadata.getDirectories()) {
                if (directory.containsTag(TAG_DATETIME_ORIGINAL)) {
                    return directory.getDate(TAG_DATETIME_ORIGINAL);
                }
            }
        }

        throw new IllegalStateException("Could not obtain creation date for '" + path + "'.");
    }

    @Nullable
    @SneakyThrows
    private String uploadPhotos(String placeId, String albumId, Path path, Predicate<Path> pathFilter, @Nullable Integer albumMainPhotoPosition) {
        try (Stream<Path> paths = Files.list(path)) {
            String batchId = UUID.randomUUID().toString();
            boolean anyUploaded = uploadPhotos(placeId, albumId, batchId, paths.filter(pathFilter), albumMainPhotoPosition);

            // TODO: When no photos are uploaded, the future always times out.
            if (asyncUploadingEnabled) {
                log.info("Waiting for photos to be processed...");

                CompletableFuture<Void> future = registerUpload(batchId);

                try {
                    future.get(ASYNC_UPLOADING_TIMEOUT.toSeconds(), TimeUnit.SECONDS);
                }
                catch (TimeoutException e) {
                    log.warn("Timed out when waiting for uploading completion.");
                    pendingAsyncUploads.remove(batchId);
                }
                catch (Exception e) {
                    log.error("An unexpected error occurred while waiting for uploading completion.", e);
                    pendingAsyncUploads.remove(batchId);
                }
            }

            return anyUploaded ? batchId : null;
        }
        finally {
            uploadedPhotoRepository.deleteByUploadedBefore(Instant.now().minus(UPLOADED_PHOTOS_RETENTION_POLICY));
        }
    }

    private CompletableFuture<Void> registerUpload(String batchId) {
        CompletableFuture<Void> future = new CompletableFuture<>();
        pendingAsyncUploads.put(batchId, future);
        return future;
    }

    @SneakyThrows
    private boolean uploadPhotos(String placeId, String albumId, String batchId, Stream<Path> paths, @Nullable Integer albumMainPhotoPosition) {
        Queue<Path> queue = paths.sorted(comparing(PhotoService::getPhotoCreationTime)).collect(toCollection(LinkedList::new));

        int expectedBatchSize = queue.size();

        int currentParallelRequestsCount = 1;
        int position = 1;

        while (!queue.isEmpty()) {
            List<Future<Double>> futures = new ArrayList<>();
            for (int i = 0; i < currentParallelRequestsCount && !queue.isEmpty(); ++i) {
                Path submittedPath = queue.remove();
                int submittedPosition = position++;

                futures.add(
                    executorService.submit(() -> uploadPhoto(placeId, albumId, batchId, expectedBatchSize, submittedPosition, submittedPath, albumMainPhotoPosition)));
            }

            double sum = 0;
            for (Future<Double> future : futures) {
                try {
                    sum += future.get();
                }
                catch (ExecutionException e) {
                    log.error("An exception occurred in the worker thread.", e.getCause());
                    throw e;
                }
            }
            double averageProcessingSpeed = sum / futures.size();
            currentParallelRequestsCount = Math.min(availableWorkers, (int) Math.ceil(averageProcessingSpeed));

            log.info("Totally {}/{} photos were uploaded.", position - 1, position - 1 + queue.size());
        }

        return position > 1;
    }

    @SneakyThrows
    private double uploadPhoto(String placeId, String albumId, String batchId, int expectedBatchSize, int batchPosition,
            Path path, @Nullable Integer albumMainPhotoPosition) {
        long start = System.currentTimeMillis();
        byte[] data = photoFetcher.fetch(path);
        retryTemplate.execute(context -> {
            doUploadPhoto(placeId, albumId, getPhotoName(), batchId, expectedBatchSize, batchPosition, data, albumMainPhotoPosition);
            return null;
        });
        long uploadDuration = (System.currentTimeMillis() - start) / 1000;
        double fileSize = data.length / (1024.0 * 1024.0);
        uploadedPhotoRepository.save(new UploadedPhoto(path.toString().toLowerCase(), Instant.now()));
        return uploadDuration == 0 ? 0 : 8 * fileSize / uploadDuration;
    }

    private void doUploadPhoto(String placeId, String albumId, String fileName, String batchId, int expectedBatchSize, int batchPosition,
            byte[] data, @Nullable Integer albumMainPhotoPosition) {
        if (asyncUploadingEnabled) {
            Map<String, Object> args = new HashMap<>();
            args.put("agentId", agentIdentifier);
            args.put("fileName", fileName);
            args.put("albumId", albumId);
            args.put("batchId", batchId);
            args.put("expectedBatchSize", expectedBatchSize);
            args.put("batchPosition", batchPosition);
            args.put("albumMainPhotoPosition", albumMainPhotoPosition);
            args.put("data", Base64.getEncoder().encodeToString(data));

            coreClient.createEvent(PHOTO_UPLOADING_TRIGGERED_EVENT_NAME, args);
        }
        else {
            coreClient.uploadPhoto(placeId, albumId, fileName, batchId, expectedBatchSize, batchPosition, data);
        }
    }

    @SneakyThrows
    private List<SynchronizedFolder> getSynchronizedFolders() {
        Optional<Configuration> configuration = configurationRepository.findById(SYNCHRONIZED_FOLDERS_CONFIGURATION_KEY);
        if (configuration.isEmpty()) {
            return List.of();
        }

        return objectMapper.readValue(configuration.get().getValue(), new TypeReference<>() {});
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

    private static String getPhotoName() {
        return UUID.randomUUID() + JPG_SUFFIX;
    }

    private static String getExpectedAlbumName(String placeName, Instant start) {
        return String.join(" ", placeName, start.atZone(ZoneId.systemDefault()).format(DateTimeFormatter.ofPattern("d.M.yyyy")));
    }

    @Override
    public Map<String, Object> getContextData() {
        Map<String, Object> contextData = new HashMap<>();
        contextData.put(SYNCHRONIZED_FOLDERS_CONFIGURATION_KEY, getAndUpdateNonExpiredSynchronizedFolders());
        if (defaultPhotoFolder != null) {
            contextData.put(DEFAULT_PHOTO_FOLDER_CONFIGURATION_KEY, defaultPhotoFolder.normalize().toAbsolutePath().toString().replace('\\', '/'));
        }
        return contextData;
    }
}
