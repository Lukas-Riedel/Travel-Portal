package cz.lriedel.agent.client;

import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.LoggingContext;
import lombok.SneakyThrows;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.stereotype.Component;

import java.util.function.Supplier;

import static cz.lriedel.agent.LoggingContext.TRANSACTION_ID_HEADER;

@Component
public class HttpEntityProvider {

    private final ObjectMapper objectMapper;
    private final LoggingContext loggingContext;
    private final Supplier<String> tokenSupplier;

    public HttpEntityProvider(ObjectMapper objectMapper, LoggingContext loggingContext, Supplier<String> tokenSupplier) {
        this.objectMapper = objectMapper;
        this.loggingContext = loggingContext;
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

        String token = tokenSupplier.get();
        if (token != null) {
            httpHeaders.setBearerAuth(token);
        }

        String transactionId = loggingContext.getTransactionId();
        if (transactionId != null) {
            httpHeaders.add(TRANSACTION_ID_HEADER, transactionId);
        }

        return httpHeaders;
    }
}
