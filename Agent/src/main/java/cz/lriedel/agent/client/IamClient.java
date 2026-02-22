package cz.lriedel.agent.client;

import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.model.api.IamResponse;
import cz.lriedel.agent.model.request.TokenPrototype;
import lombok.SneakyThrows;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.http.HttpEntity;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import java.util.Objects;

import static cz.lriedel.agent.AgentApplicationConfiguration.IAM_SERVICE_QUALIFIER;

@Component
public class IamClient {

    private static final String DEFAULT_TOKEN_SCOPE = "openid offline_access";

    private static final String CREATE_TOKEN_ENDPOINT_PATH = "/token";

    private final RestTemplate restTemplate;
    private final ObjectMapper objectMapper;

    public IamClient(@Qualifier(IAM_SERVICE_QUALIFIER) RestTemplate restTemplate, ObjectMapper objectMapper) {
        this.restTemplate = restTemplate;
        this.objectMapper = objectMapper;
    }

    @SneakyThrows
    public IamResponse createUserToken(String username, String password) {
        TokenPrototype tokenPrototype = TokenPrototype.builder().username(username).password(password).scope(DEFAULT_TOKEN_SCOPE).build();

        return sendTokenRequest(tokenPrototype);
    }

    @SneakyThrows
    public IamResponse createClientToken(String clientId, String clientSecret) {
        TokenPrototype tokenPrototype = TokenPrototype.builder().clientId(clientId).clientSecret(clientSecret).build();

        return sendTokenRequest(tokenPrototype);
    }

    @SneakyThrows
    public IamResponse createToken(String refreshToken) {
        TokenPrototype tokenPrototype = TokenPrototype.builder().refreshToken(refreshToken).scope(DEFAULT_TOKEN_SCOPE).build();

        return sendTokenRequest(tokenPrototype);
    }

    @SneakyThrows
    private IamResponse sendTokenRequest(TokenPrototype tokenPrototype) {
        return Objects.requireNonNull(
                restTemplate.postForObject(CREATE_TOKEN_ENDPOINT_PATH, new HttpEntity<>(objectMapper.writeValueAsBytes(tokenPrototype)),
                        IamResponse.class));
    }
}
