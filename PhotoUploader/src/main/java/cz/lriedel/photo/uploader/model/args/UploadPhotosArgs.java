package cz.lriedel.photo.uploader.model.args;

import org.apache.commons.lang.Validate;
import org.springframework.lang.Nullable;

import java.nio.file.Path;
import java.util.Objects;

public record UploadPhotosArgs(long placeId, @Nullable Long timestamp, @Nullable Long albumId, @Nullable Integer mainPhotoPosition, Path path) {
    
    public UploadPhotosArgs {
        Validate.isTrue(placeId > 0, "Invalid place identifier.");
        Validate.isTrue(timestamp != null || albumId != null, "Either timestamp or album identifier must be set.");
        Validate.isTrue(timestamp == null || albumId == null, "Either timestamp or album identifier must be set, but not both.");
        Validate.isTrue(mainPhotoPosition == null || mainPhotoPosition > 0, "The main photo position must be either a positive number, or not set.");
        Objects.requireNonNull(path, "The path must be set.");
        Validate.isTrue(path.toFile().exists(), "The directory does not exist.");
    }
}
