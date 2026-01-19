package cz.lriedel.agent.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import org.springframework.lang.Nullable;

import java.time.Instant;

@JsonIgnoreProperties(ignoreUnknown = true)
public record Date(Instant start, @Nullable Album album) {

}
