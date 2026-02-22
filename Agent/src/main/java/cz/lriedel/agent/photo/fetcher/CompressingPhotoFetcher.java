package cz.lriedel.agent.photo.fetcher;

import cz.lriedel.agent.MozJpegService;
import lombok.SneakyThrows;
import lombok.extern.slf4j.Slf4j;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.autoconfigure.condition.ConditionalOnMissingBean;
import org.springframework.stereotype.Component;

import java.nio.file.Files;
import java.nio.file.Path;
import java.util.UUID;

@Slf4j
@Component
@ConditionalOnMissingBean(StandardPhotoFetcher.class)
class CompressingPhotoFetcher implements PhotoFetcher {

    private final MozJpegService mozJpegService;
    private final float outputQuality;

    CompressingPhotoFetcher(MozJpegService mozJpegService, @Value("${agent.photo.compression.rate}") float outputQuality) {
        this.mozJpegService = mozJpegService;
        this.outputQuality = outputQuality;
    }

    @SneakyThrows
    @Override
    public byte[] fetch(Path path) {
        Path tempOutputWithoutMetadata = Files.createTempFile(UUID.randomUUID().toString(), null);

        try {
            mozJpegService.compress(path, tempOutputWithoutMetadata, (int) (100 * outputQuality));

            return Files.readAllBytes(tempOutputWithoutMetadata);
        }
        finally {
            Files.deleteIfExists(tempOutputWithoutMetadata);
        }
    }
}
