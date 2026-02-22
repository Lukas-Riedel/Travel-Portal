package cz.lriedel.agent.model.args;

import org.apache.commons.lang3.Validate;

import java.nio.file.Path;
import java.time.Instant;

public record FolderSynchronizationRequestedEventArgs(Path path, Instant expiration) implements EventArgs {

    public FolderSynchronizationRequestedEventArgs {
        Validate.isTrue(path.toFile().exists(), "The directory does not exist.");
        Validate.isTrue(expiration.isAfter(Instant.now()), "The expiration must be in the future.");
    }
}
