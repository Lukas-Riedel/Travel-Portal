package cz.lriedel.agent.model.request;

import lombok.Builder;
import org.apache.commons.lang3.Validate;

import javax.annotation.Nullable;

@Builder
public record EventPrototype(String name, @Nullable Object args) {

    public EventPrototype {
        Validate.notBlank(name, "The event name cannot be blank.");
    }
}
