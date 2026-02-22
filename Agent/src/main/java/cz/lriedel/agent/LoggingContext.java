package cz.lriedel.agent;

import org.springframework.lang.Nullable;
import org.springframework.stereotype.Component;
import cz.lriedel.agent.model.api.DeviceType;

@Component
public class LoggingContext {

    public static final String TRANSACTION_ID_HEADER = "Transaction-Id";
    public static final String REQUEST_ORIGIN_HEADER = "Request-Origin";

    private final ThreadLocal<String> transactionId = new ThreadLocal<>();

    @Nullable
    public String getTransactionId() {
        return transactionId.get();
    }

    public void setTransactionId(String transactionId) {
        this.transactionId.set(transactionId);
    }

    public String getRequestOrigin() {
        return DeviceType.AGENT.getValue();
    }
}
