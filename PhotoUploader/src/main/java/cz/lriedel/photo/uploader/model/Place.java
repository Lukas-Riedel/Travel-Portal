package cz.lriedel.photo.uploader.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

@JsonIgnoreProperties(ignoreUnknown = true)
public record Place(Date[] dates) {
}
