package cz.lriedel.agent;

import java.io.InputStream;
import java.io.OutputStream;
import java.net.URL;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.StandardCopyOption;
import java.util.UUID;
import java.util.zip.ZipEntry;
import java.util.zip.ZipInputStream;

import org.apache.commons.lang3.Validate;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Service;

import jakarta.annotation.PostConstruct;
import lombok.SneakyThrows;
import lombok.extern.slf4j.Slf4j;

@Slf4j
@Service
public class MozJpegService {

    private static final String MOZJPEG_URL = "https://mozjpeg.codelove.de/bin/mozjpeg_4.1.1_x64.zip";

    private static final String MOJZPEG_DIRECTORY_NAME = "mozjpeg";

    private static final String ZIP_FILE_EXTENSION = ".zip";

    private final Path mozjpegDirectory;

    public MozJpegService(@Value("${data.directory}") Path dataDirectory) {
        this.mozjpegDirectory = dataDirectory.resolve(MOJZPEG_DIRECTORY_NAME);
    }

    @SneakyThrows
    @PostConstruct
    public void installMozJpeg() {
        if (getCJpegExeFilePath().toFile().exists()) {
            log.info("MozJPEG is already installed, skipping the installation...");
            return;
        }

        Files.createDirectories(mozjpegDirectory);
        Path tempZip = Files.createTempFile(UUID.randomUUID().toString(), ZIP_FILE_EXTENSION);

        try (InputStream in = new URL(MOZJPEG_URL).openStream()) {
            Files.copy(in, tempZip, StandardCopyOption.REPLACE_EXISTING);
        }

        try (ZipInputStream zip = new ZipInputStream(Files.newInputStream(tempZip))) {
            ZipEntry entry;
            while ((entry = zip.getNextEntry()) != null) {
                Path outPath = mozjpegDirectory.resolve(entry.getName());
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
        log.info("MozJPEG has successfully been installed...");
    }

    @SneakyThrows
    public void compress(Path input, Path output, int quality) {
        ProcessBuilder pb = new ProcessBuilder(
            getCJpegExeFilePath().toAbsolutePath().toString(),
            "-quality", String.valueOf(quality),
            "-progressive",
            "-optimize",
            "-outfile", output.toAbsolutePath().toString()
        );
        pb.redirectErrorStream(true);

        Process process = pb.start();

        try (OutputStream stdin = process.getOutputStream();
            InputStream fis = Files.newInputStream(input)) {
            fis.transferTo(stdin);
        }

        int exitCode = process.waitFor();
        if (exitCode != 0) {
            throw new RuntimeException("MozJPEG failed with exit code " + exitCode + ".");
        }
    }

    private Path getCJpegExeFilePath() {
        return mozjpegDirectory.resolve("mozjpeg_4.1.1_x64").resolve("shared").resolve("tools").resolve("cjpeg.exe");
    }
}
