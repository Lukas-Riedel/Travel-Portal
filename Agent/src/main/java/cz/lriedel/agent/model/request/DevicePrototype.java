package cz.lriedel.agent.model.request;

import com.fasterxml.jackson.annotation.JsonValue;
import lombok.Builder;
import lombok.SneakyThrows;
import org.apache.commons.lang3.Validate;
import org.springframework.lang.Nullable;

import java.net.InetAddress;

@Builder
public record DevicePrototype(String id, @Nullable Object data) {

    public DevicePrototype {
        Validate.notBlank(id, "The device identifier cannot be blank.");
    }

    @JsonValue
    public String type() {
        // TODO: Use enum constant for Agent.
        return "agent";
    }

    @JsonValue
    @SneakyThrows
    public String name() {
        return InetAddress.getLocalHost().getHostName();
    }
}
