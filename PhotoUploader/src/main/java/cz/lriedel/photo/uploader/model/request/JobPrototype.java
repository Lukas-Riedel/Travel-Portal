package cz.lriedel.photo.uploader.model.request;

import java.util.Map;

public record JobPrototype(String action, Map<String, Object> args) {
}