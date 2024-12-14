package cz.lriedel.photo.uploader.model.args;

import org.apache.commons.lang.Validate;

import java.nio.file.Path;

public record ReplacePhotoArgs(long placeId, long albumId, long replacedPhotoId, Path path) {

    public ReplacePhotoArgs {
        Validate.isTrue(placeId > 0, "Invalid place identifier.");
        Validate.isTrue(albumId > 0, "Invalid album identifier.");
        Validate.isTrue(replacedPhotoId > 0, "Invalid photo identifier.");
        Validate.isTrue(path.toFile().exists(), "The photo does not exist.");
    }
}
