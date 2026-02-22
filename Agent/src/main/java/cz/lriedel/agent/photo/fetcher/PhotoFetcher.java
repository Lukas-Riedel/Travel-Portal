package cz.lriedel.agent.photo.fetcher;

import java.nio.file.Path;

public interface PhotoFetcher {

    byte[] fetch(Path path);
}
