package cz.lriedel.agent.model.request;

import org.springframework.lang.Nullable;

public record DevicePrototype(String id, String type, String name, @Nullable Object data) {
}
