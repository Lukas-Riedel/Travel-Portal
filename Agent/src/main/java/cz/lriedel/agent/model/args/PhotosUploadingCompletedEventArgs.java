package cz.lriedel.agent.model.args;

import org.apache.commons.lang3.Validate;

public record PhotosUploadingCompletedEventArgs(String batchId) implements EventArgs {

    public PhotosUploadingCompletedEventArgs {
        Validate.notBlank(batchId, "The batch identifier cannot be blank.");
    }
}
