package cz.lriedel.agent;

import jakarta.annotation.PostConstruct;
import lombok.SneakyThrows;
import lombok.extern.slf4j.Slf4j;
import org.apache.commons.lang3.SystemUtils;
import org.apache.commons.lang3.Validate;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import java.io.InputStream;
import java.io.OutputStream;
import java.net.URL;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.util.UUID;
import java.util.zip.ZipEntry;
import java.util.zip.ZipInputStream;

@Slf4j
@Service
public class MozJpegService {

    private static final String MOZJPEG_WINDOWS_EXE_FILE_NAME = "mozjpeg_4.1.1_x64";
    private static final String MOZJPEG_WINDOWS_DOWNLOAD_URL = "https://mozjpeg.codelove.de/bin/" + MOZJPEG_WINDOWS_EXE_FILE_NAME + ".zip";

    private static final String MOJZPEG_DIRECTORY_NAME = "mozjpeg";

    private static final String ZIP_FILE_EXTENSION = ".zip";

    private final Path mozJpegDirectory;

    public MozJpegService(@Value("${agent.core.data.directory}") Path dataDirectory) {
        this.mozJpegDirectory = dataDirectory.resolve(MOJZPEG_DIRECTORY_NAME);
    }

    @SneakyThrows
    @PostConstruct
    public void installMozJpeg() {
        if (SystemUtils.IS_OS_WINDOWS) {
            if (getCJpegExeFilePath().toFile().exists()) {
                log.info("MozJPEG is already installed, skipping the installation...");
                return;
            }

            Files.createDirectories(mozJpegDirectory);
            Path tempZip = Files.createTempFile(UUID.randomUUID().toString(), ZIP_FILE_EXTENSION);

            try (InputStream in = new URL(MOZJPEG_WINDOWS_DOWNLOAD_URL).openStream()) {
                Files.copy(in, tempZip, StandardCopyOption.REPLACE_EXISTING);
            }

            try (ZipInputStream zip = new ZipInputStream(Files.newInputStream(tempZip))) {
                ZipEntry entry;
                while ((entry = zip.getNextEntry()) != null) {
                    Path outPath = mozJpegDirectory.resolve(entry.getName());
                    if (entry.isDirectory()) {
                        Files.createDirectories(outPath);
                    }
                    else {
                        Files.createDirectories(outPath.getParent());
                        Files.copy(zip, outPath, StandardCopyOption.REPLACE_EXISTING);
                    }
                    zip.closeEntry();
                }
            }

            Files.delete(tempZip);

            Validate.isTrue(getCJpegExeFilePath().toFile().exists(), "Installation failed. Unable to locate '" + getCJpegExeFilePath() + "'.");
            log.info("MozJPEG has been successfully installed...");
        }
        else {
            Validate.isTrue(isCJpegCommandAvailable(), "The 'cjpeg' command is not available.");
        }
    }

    @SneakyThrows
    public void compress(Path input, Path output, int quality) {
        Process cjpeg = startCJpeg(output, quality);

        Process djpeg = null;
        if (!SystemUtils.IS_OS_WINDOWS) {
            djpeg = startDJpeg(input);
        }

        try (InputStream in = djpeg != null ? djpeg.getInputStream() : Files.newInputStream(input);
             OutputStream out = cjpeg.getOutputStream()) {
            in.transferTo(out);
        }

        if (djpeg != null) {
            waitForSuccess(djpeg, "djpeg");
        }

        waitForSuccess(cjpeg, "cjpeg");
    }

    @SneakyThrows
    private Process startDJpeg(Path input) {
        return new ProcessBuilder("djpeg", input.toAbsolutePath().toString()).start();
    }

    @SneakyThrows
    private Process startCJpeg(Path output, int quality) {
        return new ProcessBuilder(SystemUtils.IS_OS_WINDOWS
                        ? getCJpegExeFilePath().toAbsolutePath().toString() : "cjpeg",
                "-quality", String.valueOf(quality),
                "-outfile", output.toAbsolutePath().toString(),
                "-progressive",
                "-optimize"
        ).start();
    }

    @SneakyThrows
    private void waitForSuccess(Process process, String processName) {
        String error = new String(process.getErrorStream().readAllBytes());

        int exitCode = process.waitFor();
        if (exitCode != 0) {
            throw new IllegalStateException(processName + " failed with exit code " + exitCode + ". Reason: " + error);
        }
    }

    private Path getCJpegExeFilePath() {
        return mozJpegDirectory.resolve(MOZJPEG_WINDOWS_EXE_FILE_NAME).resolve("shared").resolve("tools").resolve("cjpeg.exe");
    }

    private boolean isCJpegCommandAvailable() {
        try {
            Process process = new ProcessBuilder("cjpeg", "-version").start();
            return process.waitFor() == 0;
        }
        catch (Exception e) {
            return false;
        }
    }
}
