package cz.lriedel.photo.uploader.model.request;

import java.nio.file.Path;
import java.util.Objects;

import org.apache.commons.lang.Validate;
import org.springframework.lang.Nullable;

public record UploadPrototype(long placeId, @Nullable Long timestamp, @Nullable Long albumId, Path path) {

    public UploadPrototype {
        Validate.isTrue(placeId > 0, "Invalid place identifier.");
        Validate.isTrue(timestamp != null || albumId != null, "Either timestamp or album identifier must be set.");
        Validate.isTrue(timestamp == null || albumId == null, "Either timestamp or album identifier must be set, but not both.");
        Objects.requireNonNull(path, "The path must be set.");
    }
}
