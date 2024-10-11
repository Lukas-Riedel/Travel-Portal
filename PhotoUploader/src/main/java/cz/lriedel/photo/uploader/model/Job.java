package cz.lriedel.photo.uploader.model;

import java.util.Map;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

@JsonIgnoreProperties(ignoreUnknown = true)
public record Job(long id, Map<String, Object> args) {
}