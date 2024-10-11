package cz.lriedel.photo.uploader.fetcher;

import java.io.IOException;
import java.nio.file.Path;

public interface PhotoFetcher {

    byte[] fetch(Path path) throws IOException;
}
