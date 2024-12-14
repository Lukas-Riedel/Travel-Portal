package cz.lriedel.photo.uploader.model.request;

import cz.lriedel.photo.uploader.model.Photo;

import javax.annotation.Nullable;

public record PhotoPrototype(String name, @Nullable Integer position, @Nullable Long replacePhotoId, String data) {

    public PhotoPrototype(String name, int position, String data) {
        this(name, position, null, data);
    }

    public PhotoPrototype(String name, long replacedPhotoId, String data) {
        this(name, null, replacedPhotoId, data);
    }
}
