package cz.lriedel.photo.uploader;

import com.fasterxml.jackson.databind.ObjectMapper;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.stereotype.Component;

import java.util.Objects;

@Component
public class HttpEntityProvider {

    private final ObjectMapper objectMapper;
    private final AccessTokenProvider accessTokenProvider;

    public HttpEntityProvider(ObjectMapper objectMapper, AccessTokenProvider accessTokenProvider) {
        this.objectMapper = Objects.requireNonNull(objectMapper);
        this.accessTokenProvider = Objects.requireNonNull(accessTokenProvider);
    }

    public HttpEntity<Void> getEmptyHttpEntity() {
        return new HttpEntity<>(getHttpHeaders());
    }

    public <T> HttpEntity<String> getHttpEntity(T requestBody) {
        try {
            return new HttpEntity<>(objectMapper.writeValueAsString(requestBody), getHttpHeaders());
        }
        catch (Exception e) {
            throw new RuntimeException(e);
        }
    }

    private HttpHeaders getHttpHeaders() {
        HttpHeaders httpHeaders = new HttpHeaders();
        httpHeaders.setBearerAuth(accessTokenProvider.getAccessToken());
        return httpHeaders;
    }
}
