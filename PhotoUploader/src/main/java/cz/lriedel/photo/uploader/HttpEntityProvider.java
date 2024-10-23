package cz.lriedel.photo.uploader;

import java.util.Objects;

import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.stereotype.Component;

import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;

@Component
public class HttpEntityProvider {

    private final ObjectMapper objectMapper;
    private final AccessTokenProvider accessTokenProvider;

    public HttpEntityProvider(ObjectMapper objectMapper, AccessTokenProvider accessTokenProvider) {
        this.objectMapper = Objects.requireNonNull(objectMapper);
        this.accessTokenProvider = Objects.requireNonNull(accessTokenProvider);
    }

    public HttpEntity<Void> getEmptyHttpEntity() throws JsonProcessingException {
        return new HttpEntity<>(getHttpHeaders());
    }

    public <T> HttpEntity<String> getHttpEntity(T requestBody) throws JsonProcessingException {
        return new HttpEntity<>(objectMapper.writeValueAsString(requestBody), getHttpHeaders());
    }

    private HttpHeaders getHttpHeaders() throws JsonProcessingException {
        HttpHeaders httpHeaders = new HttpHeaders();
        httpHeaders.setBearerAuth(accessTokenProvider.getAccessToken());
        return httpHeaders;
    }
}
