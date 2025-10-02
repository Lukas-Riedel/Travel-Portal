package cz.lriedel.agent.client;

import cz.lriedel.agent.model.Album;
import cz.lriedel.agent.model.IamResponse;
import cz.lriedel.agent.model.request.DevicePrototype;
import cz.lriedel.agent.model.request.EventPrototype;
import cz.lriedel.agent.model.request.PhotoPrototype;
import cz.lriedel.agent.model.request.TokenPrototype;
import lombok.SneakyThrows;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import java.net.InetAddress;
import java.util.Base64;
import java.util.Map;
import java.util.Objects;

import static cz.lriedel.agent.AgentApplicationConfiguration.CORE_SERVICE_QUALIFIER;
import static cz.lriedel.agent.AgentApplicationConfiguration.IAM_SERVICE_QUALIFIER;

@Component
public class IamClient {

    private static final String DEFAULT_TOKEN_SCOPE = "openid offline_access";

    private final RestTemplate restTemplate;

    IamClient(@Qualifier(IAM_SERVICE_QUALIFIER) RestTemplate restTemplate) {
        this.restTemplate = restTemplate;
    }

    @SneakyThrows
    public IamResponse createToken(String username, String password) {
        TokenPrototype tokenPrototype = new TokenPrototype(username, password, null, DEFAULT_TOKEN_SCOPE);
        return Objects.requireNonNull(restTemplate.postForObject("/token", new HttpEntity<>(tokenPrototype), IamResponse.class));
    }

    @SneakyThrows
    public IamResponse createToken(String refreshToken) {
        TokenPrototype tokenPrototype = new TokenPrototype(null, null, refreshToken, DEFAULT_TOKEN_SCOPE);
        return Objects.requireNonNull(restTemplate.postForObject("/token", new HttpEntity<>(tokenPrototype), IamResponse.class));
    }
}
