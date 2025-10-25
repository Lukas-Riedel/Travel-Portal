package cz.lriedel.agent.photo;

import java.nio.file.Path;
import java.time.Instant;

public record SynchronizedFolder(Path path, Instant expiration) {
}
