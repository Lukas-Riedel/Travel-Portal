package cz.lriedel.agent.photo.fetcher;

import java.io.FileInputStream;
import java.io.InputStream;
import java.nio.file.Path;

import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.stereotype.Component;

import lombok.SneakyThrows;

@Component
@ConditionalOnProperty(value = "output.quality", havingValue = "1")
final class StandardPhotoFetcher implements PhotoFetcher {

    @SneakyThrows
    @Override
    public byte[] fetch(Path path) {
        try (InputStream inputStream = new FileInputStream(path.toFile())) {
            return inputStream.readAllBytes();
        }
    }
}
