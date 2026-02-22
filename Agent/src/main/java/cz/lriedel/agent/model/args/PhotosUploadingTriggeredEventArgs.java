package cz.lriedel.agent.model.args;

import org.apache.commons.lang3.Validate;
import org.springframework.lang.Nullable;

import java.nio.file.Path;
import java.time.Instant;

public record PhotosUploadingTriggeredEventArgs(String placeId, String placeName, @Nullable Instant timestamp, @Nullable String albumId,
                                                @Nullable Integer mainPhotoPosition, Path path) implements EventArgs {

    public PhotosUploadingTriggeredEventArgs {
        Validate.notBlank(placeId, "The place identifier cannot be blank.");
        Validate.notBlank(placeName, "The place name cannot be blank.");
        Validate.isTrue(timestamp != null || (albumId != null && !albumId.isBlank()), "Either timestamp or album identifier must be set.");
        Validate.isTrue(mainPhotoPosition == null || mainPhotoPosition > 0, "The main photo position must be either a positive number, or not set.");
        Validate.isTrue(path.toFile().exists(), "The directory does not exist.");
    }
}
