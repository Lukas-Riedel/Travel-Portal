package cz.lriedel.agent.model.args;

import java.nio.file.Path;

import org.apache.commons.lang3.Validate;

public record PhotoReplacingTriggeredEventArgs(String placeId, String placeName, String albumId, String replacedPhotoId, Path path) implements EventArgs {

    public PhotoReplacingTriggeredEventArgs {
        Validate.notBlank(placeId, "The place identifier cannot be blank.");
        Validate.notBlank(placeName, "The place name cannot be blank.");
        Validate.notBlank(placeId, "The place identifier cannot be blank.");
        Validate.notBlank(replacedPhotoId, "The replaced photo identifier cannot be blank.");
        Validate.isTrue(path.toFile().exists(), "The photo does not exist.");
    }
}
