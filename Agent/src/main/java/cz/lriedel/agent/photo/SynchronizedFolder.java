package cz.lriedel.agent.photo;

import java.nio.file.Path;
import java.time.Instant;
import java.util.Objects;

public record SynchronizedFolder(Path path, Instant expiration) {

    public SynchronizedFolder {
        Objects.requireNonNull(path, "The folder path cannot be null.");
        Objects.requireNonNull(expiration, "The synchronization expiration cannot be null.");
    }
}
