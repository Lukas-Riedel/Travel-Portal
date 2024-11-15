package cz.lriedel.photo.uploader.model.args;

import java.nio.file.Path;

import org.apache.commons.lang.Validate;

public record DownloadPhotosArgs(Path path) {

    public DownloadPhotosArgs {
        Validate.isTrue(path.toFile().exists(), "The directory does not exist.");
    }
}
