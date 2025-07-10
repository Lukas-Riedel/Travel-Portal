package cz.lriedel.agent.model.request;

import javax.annotation.Nullable;

public record PhotoPrototype(String fileName, @Nullable String batchId, @Nullable Integer expectedBatchSize, @Nullable Integer batchPosition, @Nullable Long replacedPhotoId, String data) {

    public PhotoPrototype(String fileName, String batchId, int expectedBatchSize, int batchPosition, String data) {
        this(fileName, batchId, expectedBatchSize, batchPosition, null, data);
    }

    public PhotoPrototype(String fileName, long replacedPhotoId, String data) {
        this(fileName, null, null, null, replacedPhotoId, data);
    }
}
