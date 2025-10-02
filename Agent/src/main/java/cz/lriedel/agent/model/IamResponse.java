package cz.lriedel.agent.model;

import com.fasterxml.jackson.annotation.JsonIgnoreProperties;

@JsonIgnoreProperties(ignoreUnknown = true)
public record IamResponse(String accessToken, long expiresIn, String refreshToken, long refreshExpiresIn) {
}
