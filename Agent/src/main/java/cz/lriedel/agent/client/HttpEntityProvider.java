package cz.lriedel.agent.client;

import com.fasterxml.jackson.databind.ObjectMapper;
import lombok.SneakyThrows;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.stereotype.Component;

import java.util.function.Supplier;

@Component
public final class HttpEntityProvider {

    private final ObjectMapper objectMapper;
    private final Supplier<String> tokenSupplier;

    public HttpEntityProvider(ObjectMapper objectMapper, Supplier<String> tokenSupplier) {
        this.objectMapper = objectMapper;
        this.tokenSupplier = tokenSupplier;
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
        httpHeaders.setBearerAuth(tokenSupplier.get());
        return httpHeaders;
    }
}
