package cz.lriedel.photo.uploader.model.request;

import java.util.Map;
import java.util.Objects;

public record JobPrototype(String action, Map<String, Object> args) {
}
