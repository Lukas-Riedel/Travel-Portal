package cz.lriedel.agent.client;

import static cz.lriedel.agent.AgentApplicationConfiguration.IAM_SERVICE_QUALIFIER;

import java.util.Objects;

import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.stereotype.Component;
import org.springframework.util.LinkedMultiValueMap;
import org.springframework.util.MultiValueMap;
import org.springframework.web.client.RestTemplate;

import cz.lriedel.agent.model.AccessToken;
import lombok.SneakyThrows;

@Component
final class AccessTokenProvider {

    private static final String TOKEN_ENDPOINT = "/protocol/openid-connect/token";
    private static final String PASSWORD_GRANT_TYPE = "password";

    private final RestTemplate restTemplate;
    private final String clientId;
    private final String username;
    private final String password;

    private String accessToken;
    private long expiration;

    AccessTokenProvider(@Qualifier(IAM_SERVICE_QUALIFIER) RestTemplate restTemplate, @Value("${service.iam.client-id}") String clientId,
        @Value("${credentials.username}") String username, @Value("${credentials.password}") String password) {
        this.restTemplate = restTemplate;
        this.clientId = clientId;
        this.username = username;
        this.password = password;
    }

    @SneakyThrows
    public String getAccessToken() {
        if (this.expiration < System.currentTimeMillis()) {
            AccessToken response = Objects.requireNonNull(restTemplate.postForObject(TOKEN_ENDPOINT, getHttpEntity(), AccessToken.class));
            this.accessToken = response.accessToken();
            this.expiration = System.currentTimeMillis() + response.expiresIn() * 1000 / 2;
        }

        return this.accessToken;
    }

    private HttpEntity<?> getHttpEntity() {
        HttpHeaders headers = new HttpHeaders();
        headers.setContentType(MediaType.APPLICATION_FORM_URLENCODED);

        MultiValueMap<String, String> body = new LinkedMultiValueMap<>();
        body.add("grant_type", PASSWORD_GRANT_TYPE);
        body.add("client_id", clientId);
        body.add("username", username);
        body.add("password", password);

        return new HttpEntity<>(body, headers);
    }
}