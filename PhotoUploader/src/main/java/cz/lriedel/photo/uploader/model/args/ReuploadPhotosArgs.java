package cz.lriedel.photo.uploader.model.args;

import org.apache.commons.lang.Validate;

public record ReuploadPhotosArgs(long placeId, long albumId) {

    public ReuploadPhotosArgs {
        Validate.isTrue(placeId > 0, "Invalid place identifier.");
        Validate.isTrue(albumId > 0, "Invalid album identifier.");
    }
}
