package cz.lriedel.photo.uploader.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

import java.util.Map;

@JsonIgnoreProperties(ignoreUnknown = true)
public record Job(long id, Map<String, Object> args) {
}