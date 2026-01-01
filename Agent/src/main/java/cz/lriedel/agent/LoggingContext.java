package cz.lriedel.agent;

import org.springframework.lang.Nullable;
import org.springframework.stereotype.Component;

@Component
public class LoggingContext {

    public static final String TRANSACTION_ID_HEADER = "Transaction-Id";

    private final ThreadLocal<String> transactionId = new ThreadLocal<>();

    @Nullable
    public String getTransactionId() {
        return this.transactionId.get();
    }

    public void setTransactionId(String transactionId) {
        this.transactionId.set(transactionId);
    }
}
