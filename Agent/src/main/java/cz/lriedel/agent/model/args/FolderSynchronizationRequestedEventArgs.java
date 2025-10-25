package cz.lriedel.agent.model.args;

import java.nio.file.Path;
import java.time.Instant;

import org.apache.commons.lang3.Validate;

public record FolderSynchronizationRequestedEventArgs(Path path, Instant expiration) implements EventArgs {

    public FolderSynchronizationRequestedEventArgs {
        Validate.isTrue(path.toFile().exists(), "The directory does not exist.");
        Validate.isTrue(expiration.isAfter(Instant.now()), "The expiration must be in the future.");
    }
}
