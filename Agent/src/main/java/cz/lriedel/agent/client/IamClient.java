package cz.lriedel.agent.client;

import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.model.IamResponse;
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

    private final RestTemplate restTemplate;
    private final ObjectMapper objectMapper = new ObjectMapper();

    public IamClient(@Qualifier(IAM_SERVICE_QUALIFIER) RestTemplate restTemplate) {
        this.restTemplate = restTemplate;
    }

    @SneakyThrows
    public IamResponse createUserToken(String username, String password) {
        TokenPrototype tokenPrototype = TokenPrototype.builder().username(username).password(password).scope(DEFAULT_TOKEN_SCOPE).build();
        return Objects.requireNonNull(restTemplate.postForObject("/token",
                new HttpEntity<>(objectMapper.writeValueAsBytes(tokenPrototype)), IamResponse.class));
    }

    @SneakyThrows
    public IamResponse createClientToken(String clientId, String clientSecret) {
        TokenPrototype tokenPrototype = TokenPrototype.builder().clientId(clientId).clientSecret(clientSecret).build();
        return Objects.requireNonNull(restTemplate.postForObject("/token",
                new HttpEntity<>(objectMapper.writeValueAsBytes(tokenPrototype)), IamResponse.class));
    }

    @SneakyThrows
    public IamResponse createToken(String refreshToken) {
        TokenPrototype tokenPrototype = TokenPrototype.builder().refreshToken(refreshToken).scope(DEFAULT_TOKEN_SCOPE).build();
        return Objects.requireNonNull(restTemplate.postForObject("/token",
                new HttpEntity<>(objectMapper.writeValueAsBytes(tokenPrototype)), IamResponse.class));
    }
}
