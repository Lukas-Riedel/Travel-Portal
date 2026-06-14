package cz.lriedel.agent.model.request;

import com.fasterxml.jackson.annotation.JsonProperty;
import cz.lriedel.agent.model.api.DeviceType;
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

    @JsonProperty
    public String type() {
        return DeviceType.AGENT.getValue();
    }

    @JsonProperty
    @SneakyThrows
    public String name() {
        // TODO: Propagate from the caller. Make the name configurable.
        String hostname = System.getenv("APP_HOSTNAME");
        return hostname != null ? hostname : InetAddress.getLocalHost().getHostName();
    }
}
