package cz.lriedel.agent.model.args;

import java.nio.file.Path;
import java.util.Objects;

import org.apache.commons.lang3.Validate;
import org.springframework.lang.Nullable;

public record PhotosUploadingTriggeredEventArgs(long placeId, @Nullable Long timestamp, @Nullable Long albumId,
                                                @Nullable Integer mainPhotoPosition, Path path) implements EventArgs {
    
    public PhotosUploadingTriggeredEventArgs {
        Validate.isTrue(placeId > 0, "Invalid place identifier.");
        Validate.isTrue(timestamp != null || albumId != null, "Either timestamp or album identifier must be set.");
        Validate.isTrue(mainPhotoPosition == null || mainPhotoPosition > 0, "The main photo position must be either a positive number, or not set.");
        Objects.requireNonNull(path, "The path must be set.");
        Validate.isTrue(path.toFile().exists(), "The directory does not exist.");
    }
}
