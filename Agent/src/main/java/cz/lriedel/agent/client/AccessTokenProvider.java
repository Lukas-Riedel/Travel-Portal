package cz.lriedel.agent.client;

import static cz.lriedel.agent.AgentApplicationConfiguration.IAM_SERVICE_QUALIFIER;

import java.util.Objects;

import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.agent.model.IamResponse;
import cz.lriedel.agent.model.request.TokenPrototype;
import lombok.SneakyThrows;

@Component
final class AccessTokenProvider {

    private static final double ACCESS_TOKEN_VALIDITY_MULTIPLIER = 0.95;

    private final ObjectMapper objectMapper;

    private final RestTemplate restTemplate;

    private final String username;
    private final String password;

    private String accessToken;
    private long expiration;

    AccessTokenProvider(ObjectMapper objectMapper, @Qualifier(IAM_SERVICE_QUALIFIER) RestTemplate restTemplate,
        @Value("${credentials.username}") String username, @Value("${credentials.password}") String password) {
        this.objectMapper = objectMapper;
        this.restTemplate = restTemplate;
        this.username = username;
        this.password = password;
    }

    @SneakyThrows
    public String getAccessToken() {
        if (this.expiration < System.currentTimeMillis()) {
            TokenPrototype tokenPrototype = new TokenPrototype(username, password);
            IamResponse response = Objects.requireNonNull(restTemplate.postForObject("/token",
                    new HttpEntity<>(objectMapper.writeValueAsString(tokenPrototype), new HttpHeaders()), IamResponse.class));

            this.accessToken = response.accessToken();
            this.expiration = System.currentTimeMillis() + (long) (ACCESS_TOKEN_VALIDITY_MULTIPLIER * response.expiresIn() * 1000);
        }

        return this.accessToken;
    }
}