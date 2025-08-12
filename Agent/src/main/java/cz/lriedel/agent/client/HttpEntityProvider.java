package cz.lriedel.agent.client;

import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.stereotype.Component;

import com.fasterxml.jackson.databind.ObjectMapper;

import lombok.SneakyThrows;

@Component
final class HttpEntityProvider {

    private final ObjectMapper objectMapper;
    private final AccessTokenProvider accessTokenProvider;

    HttpEntityProvider(ObjectMapper objectMapper, AccessTokenProvider accessTokenProvider) {
        this.objectMapper = objectMapper;
        this.accessTokenProvider = accessTokenProvider;
    }

    public HttpEntity<Void> getEmptyHttpEntity() {
        return new HttpEntity<>(getHttpHeaders());
    }

    @SneakyThrows
    public <T> HttpEntity<String> getHttpEntity(T requestBody) {
        return new HttpEntity<>(objectMapper.writeValueAsString(requestBody), getHttpHeaders());
    }

    private HttpHeaders getHttpHeaders() {
        HttpHeaders httpHeaders = new HttpHeaders();
        httpHeaders.setBearerAuth(accessTokenProvider.getAccessToken());
        return httpHeaders;
    }
}
