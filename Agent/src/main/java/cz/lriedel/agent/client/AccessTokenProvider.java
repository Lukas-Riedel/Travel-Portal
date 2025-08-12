package cz.lriedel.agent.client;

import java.util.Objects;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.agent.model.AccessToken;
import cz.lriedel.agent.model.request.AccessTokenPrototype;
import lombok.SneakyThrows;

@Component
final class AccessTokenProvider {

    private static final String IAM_ENDPOINT = "/iam";

    private final RestTemplate restTemplate;
    private final ObjectMapper objectMapper;
    private final String apiKey;

    private String accessToken;
    private long expiration;

    AccessTokenProvider(ObjectMapper objectMapper, RestTemplate restTemplate, @Value("${api.key}") String apiKey) {
        this.restTemplate = restTemplate;
        this.objectMapper = objectMapper;
        this.apiKey = apiKey;
    }

    @SneakyThrows
    public String getAccessToken() {
        if (this.expiration < System.currentTimeMillis()) {
            AccessTokenPrototype request = new AccessTokenPrototype(apiKey);
            AccessToken response = Objects.requireNonNull(restTemplate
                    .postForObject(IAM_ENDPOINT, objectMapper.writeValueAsString(request), AccessToken.class));
            this.accessToken = response.accessToken();
            this.expiration = System.currentTimeMillis() + response.validity() * 1000 / 2;
        }

        return this.accessToken;
    }
}