package cz.lriedel.agent.model.request;

import lombok.Builder;
import org.apache.commons.lang3.Validate;

import javax.annotation.Nullable;

@Builder
public record PhotoPrototype(String fileName, @Nullable String batchId, @Nullable Integer expectedBatchSize, @Nullable Integer batchPosition,
                             @Nullable String replacedPhotoId, String data) {

    public PhotoPrototype {
        Validate.notBlank(fileName, "The file name cannot be blank.");
        Validate.notBlank(data, "The data string cannot be blank.");
    }
}
