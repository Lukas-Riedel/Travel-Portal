package cz.lriedel.agent;

import cz.lriedel.agent.model.api.DeviceType;
import org.slf4j.MDC;
import org.springframework.lang.Nullable;
import org.springframework.stereotype.Component;

@Component
public class LoggingContext {

    public static final String TRANSACTION_ID_HEADER = "Transaction-Id";
    public static final String REQUEST_ORIGIN_HEADER = "Request-Origin";

    private static final String MDC_TRANSACTION_ID = "transaction_id";
    private static final String MDC_REQUEST_ORIGIN = "request_origin";

    public void init() {
        MDC.put(MDC_REQUEST_ORIGIN, DeviceType.AGENT.getValue());
    }

    public void clear() {
        MDC.clear();
    }

    @Nullable
    public String getTransactionId() {
        return MDC.get(MDC_TRANSACTION_ID);
    }

    public void setTransactionId(String transactionId) {
        MDC.put(MDC_TRANSACTION_ID, transactionId);
    }

    public String getRequestOrigin() {
        return DeviceType.AGENT.getValue();
    }
}
