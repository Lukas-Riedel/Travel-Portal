package cz.lriedel.photo.uploader.model;

import java.nio.file.Path;
import java.util.Objects;

import org.apache.commons.lang.Validate;
import org.springframework.lang.Nullable;

public record UploadPhotosJobArgs(long placeId, @Nullable Long timestamp, @Nullable Long albumId, Path path) {
}
