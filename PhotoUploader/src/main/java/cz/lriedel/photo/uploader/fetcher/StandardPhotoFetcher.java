package cz.lriedel.photo.uploader.fetcher;

import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.stereotype.Component;

import java.io.FileInputStream;
import java.io.IOException;
import java.io.InputStream;
import java.nio.file.Path;

@Component
@ConditionalOnProperty(value = "output.quality", havingValue = "1")
public class StandardPhotoFetcher implements PhotoFetcher {

    @Override
    public byte[] fetch(Path path) throws IOException {
        try (InputStream inputStream = new FileInputStream(path.toFile())) {
            return inputStream.readAllBytes();
        }
    }
}
