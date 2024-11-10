package cz.lriedel.photo.uploader.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

import javax.annotation.Nullable;

@JsonIgnoreProperties(ignoreUnknown = true)
public record Date(long start, @Nullable Album album) {
}
