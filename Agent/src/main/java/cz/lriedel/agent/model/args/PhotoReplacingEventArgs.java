package cz.lriedel.agent.model.args;

import org.apache.commons.lang3.Validate;

import java.nio.file.Path;

public record PhotoReplacingEventArgs(long placeId, long albumId, long replacedPhotoId, Path path) {

    public PhotoReplacingEventArgs {
        Validate.isTrue(placeId > 0, "Invalid place identifier.");
        Validate.isTrue(albumId > 0, "Invalid album identifier.");
        Validate.isTrue(replacedPhotoId > 0, "Invalid photo identifier.");
        Validate.isTrue(path.toFile().exists(), "The photo does not exist.");
    }
}
