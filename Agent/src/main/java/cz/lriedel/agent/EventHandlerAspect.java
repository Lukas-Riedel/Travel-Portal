package cz.lriedel.agent;

import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.client.CoreClient;
import cz.lriedel.agent.model.args.EventArgs;
import lombok.extern.slf4j.Slf4j;
import org.aspectj.lang.ProceedingJoinPoint;
import org.aspectj.lang.annotation.Around;
import org.aspectj.lang.annotation.Aspect;
import org.springframework.lang.Nullable;
import org.springframework.stereotype.Component;

import java.util.Map;

import static org.apache.commons.lang3.StringUtils.EMPTY;

@Slf4j
@Aspect
@Component
class EventHandlerAspect {

    private static final String PROCESSING_STARTED_EVENT_NAME = "ProcessingStarted";
    private static final String PROCESSING_ENDED_EVENT_NAME = "ProcessingEnded";
    private static final String PROCESSING_FAILED_EVENT_NAME = "ProcessingFailed";

    private static final String EVENT_NAME_PROPERTY_KEY = "name";
    private static final String EVENT_ARGS_PROPERTY_KEY = "args";
    private static final String EVENT_ARGS_RESULT_PROPERTY_KEY = "result";

    private final CoreClient coreClient;
    private final ObjectMapper objectMapper;
    private final LoggingContext loggingContext;

    EventHandlerAspect(CoreClient coreClient, ObjectMapper objectMapper, LoggingContext loggingContext) {
        this.coreClient = coreClient;
        this.objectMapper = objectMapper;
        this.loggingContext = loggingContext;
    }

    @Nullable
    @Around("@annotation(org.springframework.amqp.rabbit.annotation.RabbitHandler) && execution(* *(..))")
    public Object aroundRabbitHandler(ProceedingJoinPoint pjp) throws Throwable {
        loggingContext.init();

        Object eventArgs = pjp.getArgs()[0];
        String eventName = eventArgs.getClass().getSimpleName().replace(EventArgs.class.getSimpleName(), EMPTY);
        Map<String, Object> event = createEvent(eventName, eventArgs);

        long start = System.currentTimeMillis();
        log.info("Starting processing of '{} ({})'...", eventName, eventArgs);
        coreClient.createEvent(PROCESSING_STARTED_EVENT_NAME, event);

        try {
            Object result = pjp.proceed();

            Map<String, Object> mergedArgs = objectMapper.convertValue(eventArgs, new TypeReference<>() {});
            mergedArgs.put(EVENT_ARGS_RESULT_PROPERTY_KEY, result);

            coreClient.createEvent(PROCESSING_ENDED_EVENT_NAME, createEvent(eventName, mergedArgs));
            return null;
        }
        catch (Exception e) {
            log.error(String.format("An exception occurred when processing '%s (%s)'.", eventName, eventArgs), e);
            coreClient.createEvent(PROCESSING_FAILED_EVENT_NAME, event);
            return null;
        }
        finally {
            loggingContext.clear();
            log.info("Processing of '{} ({})' ended in {} milliseconds.", eventName, eventArgs, System.currentTimeMillis() - start);
        }
    }

    private Map<String, Object> createEvent(String name, Object args) {
        return Map.of(EVENT_NAME_PROPERTY_KEY, name, EVENT_ARGS_PROPERTY_KEY, args);
    }
}
