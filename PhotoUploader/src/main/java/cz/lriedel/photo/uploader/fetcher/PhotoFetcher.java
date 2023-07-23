package cz.lriedel.photo.uploader.fetcher;

import java.io.FileNotFoundException;
import java.io.IOException;
import java.io.InputStream;
import java.nio.file.Path;

public interface PhotoFetcher {

    byte[] fetch(Path path) throws IOException;
}
