package cz.lriedel.agent.model.request;

import lombok.Builder;
import org.apache.commons.lang3.Validate;

import org.jspecify.annotations.Nullable;

@Builder
public record EventPrototype(String name, @Nullable Object args) {

    public EventPrototype {
        Validate.notBlank(name, "The event name cannot be blank.");
    }
}
