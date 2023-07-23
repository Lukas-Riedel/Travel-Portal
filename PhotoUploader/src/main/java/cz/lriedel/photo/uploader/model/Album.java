package cz.lriedel.photo.uploader.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

@JsonIgnoreProperties(ignoreUnknown = true)
public record Album(long id, String permalink) {
}
