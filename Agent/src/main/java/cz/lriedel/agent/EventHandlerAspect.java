package cz.lriedel.agent;

import static org.apache.commons.lang3.StringUtils.EMPTY;

import java.util.Map;

import org.aspectj.lang.ProceedingJoinPoint;
import org.aspectj.lang.annotation.Around;
import org.aspectj.lang.annotation.Aspect;
import org.springframework.stereotype.Component;

import cz.lriedel.agent.client.ServiceClient;
import cz.lriedel.agent.model.args.EventArgs;
import lombok.extern.slf4j.Slf4j;

@Slf4j
@Aspect
@Component
final class EventHandlerAspect {

    private static final String EVENT_NAME_PROPERTY_KEY = "name";
    private static final String EVENT_ARGS_PROPERTY_KEY = "args";

    private final ServiceClient serviceClient;

    public EventHandlerAspect(ServiceClient serviceClient) {
        this.serviceClient = serviceClient;
    }

    @Around("@annotation(org.springframework.amqp.rabbit.annotation.RabbitHandler) && execution(* *(..))")
    public Object aroundRabbitHandler(ProceedingJoinPoint pjp) throws Throwable {
        Object eventArgs = pjp.getArgs()[0];
        String eventName = eventArgs.getClass().getSimpleName().replace(EventArgs.class.getSimpleName(), EMPTY);
        Map<String, Object> event = Map.of(EVENT_NAME_PROPERTY_KEY, eventName, EVENT_ARGS_PROPERTY_KEY, eventArgs);

        long start = System.currentTimeMillis();
        log.info("Starting processing of '{} ({})'...", eventName, eventArgs);
        serviceClient.createEvent("ProcessingStarted", event);

        try {
            return pjp.proceed();
        }
        finally {
            serviceClient.createEvent("ProcessingEnded", event);
            log.info("Processing of '{} ({})' ended in {} milliseconds.", eventName, eventArgs, System.currentTimeMillis() - start);
        }
    }
}
