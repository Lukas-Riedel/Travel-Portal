package cz.lriedel.agent.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;
import com.fasterxml.jackson.annotation.JsonProperty;

@JsonIgnoreProperties(ignoreUnknown = true)
public record AccessToken(@JsonProperty("access_token") String accessToken, @JsonProperty("expires_in") long expiresIn) {
}
