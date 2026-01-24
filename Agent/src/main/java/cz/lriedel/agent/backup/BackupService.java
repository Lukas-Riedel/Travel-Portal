package cz.lriedel.agent.backup;

import cz.lriedel.agent.persistance.SynchronizedFile;
import cz.lriedel.agent.persistance.SynchronizedFileRepository;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.time.Duration;
import java.time.Instant;
import java.time.ZoneId;
import java.time.format.DateTimeFormatter;
import java.util.List;
import java.util.Set;
import java.util.concurrent.TimeUnit;
import java.util.stream.Stream;

import static java.util.function.Predicate.not;
import static java.util.stream.Collectors.toSet;

@Slf4j
@Service
public class BackupService {

    private static final Duration SYNCHRONIZED_FILES_RETENTION_POLICY = Duration.ofDays(365);
    private static final DateTimeFormatter TARGET_FOLDER_DATE_TIME_FORMAT = DateTimeFormatter.ofPattern("yyyyMMdd'T'HHmmss");

    private final Path sourceFolder;
    private final Path targetFolder;
    private final List<String> supportedExtensions;

    private final SynchronizedFileRepository synchronizedFileRepository;

    public BackupService(@Value("${agent.backup.folder.source}") Path sourceFolder, @Value("${agent.backup.folder.target}") Path targetFolder,
            @Value("${agent.backup.file.extensions}") List<String> supportedExtensions, SynchronizedFileRepository synchronizedFileRepository) {
        this.sourceFolder = sourceFolder;
        this.targetFolder = targetFolder;
        this.supportedExtensions = supportedExtensions;
        this.synchronizedFileRepository = synchronizedFileRepository;
    }

    @Scheduled(fixedDelayString = "${agent.backup.synchronization.interval}", timeUnit = TimeUnit.SECONDS)
    public void synchronizeFiles() {
        Set<Path> synchronizedFiles = synchronizedFileRepository.findAll().stream().map(SynchronizedFile::getPath).map(Path::of).map(Path::normalize)
                .collect(toSet());

        try (Stream<Path> allFiles = Files.walk(sourceFolder)) {
            Set<Path> nonSynchronizedFiles = allFiles.filter(Files::isRegularFile).map(Path::normalize)
                    .filter(path -> supportedExtensions.stream().anyMatch(ext -> path.toString().toLowerCase().endsWith(ext.toLowerCase())))
                    .filter(not(synchronizedFiles::contains)).collect(toSet());

            if (!nonSynchronizedFiles.isEmpty()) {
                Path folder = Files.createDirectories(
                        targetFolder.resolve(Instant.now().atZone(ZoneId.systemDefault()).format(TARGET_FOLDER_DATE_TIME_FORMAT)));

                log.info("Synchronizing {} files to '{}'...", nonSynchronizedFiles.size(), folder);
                for (Path nonSynchronizedFile : nonSynchronizedFiles) {
                    Files.copy(nonSynchronizedFile, folder.resolve(nonSynchronizedFile.getFileName()), StandardCopyOption.REPLACE_EXISTING);
                    synchronizedFileRepository.save(new SynchronizedFile(nonSynchronizedFile.toString(), Instant.now()));
                }
            }
        }
        catch (IOException e) {
            log.warn("The folder '{}' is not available, no files will be synchronized.", sourceFolder);
        }
        finally {
            synchronizedFileRepository.deleteByUploadedBefore(Instant.now().minus(SYNCHRONIZED_FILES_RETENTION_POLICY));
        }
    }
}
