package cz.lriedel.photo.uploader.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

@JsonIgnoreProperties(ignoreUnknown = true)
public record Photo(long id, String url) {
}