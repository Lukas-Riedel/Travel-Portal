package cz.lriedel.photo.uploader.model.args;

import org.apache.commons.lang.Validate;

import java.nio.file.Path;

public record DownloadPhotosArgs(Path path) {

    public DownloadPhotosArgs {
        Validate.isTrue(path.toFile().exists(), "The directory does not exist.");
    }
}
