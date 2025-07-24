package cz.lriedel.agent.event;

import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.agent.client.ServiceClient;
import lombok.extern.slf4j.Slf4j;

import java.util.HashMap;
import java.util.Map;

@Slf4j
public abstract class AbstractEventHandler<EVENT_ARGS> implements EventHandler<EVENT_ARGS> {

    protected final ObjectMapper objectMapper;

    private final ServiceClient serviceClient;
    private final Class<EVENT_ARGS> eventArgsClass;

    protected AbstractEventHandler(ObjectMapper objectMapper, ServiceClient serviceClient, Class<EVENT_ARGS> eventArgsClass) {
        this.objectMapper = objectMapper;
        this.serviceClient = serviceClient;
        this.eventArgsClass = eventArgsClass;
    }

    public final void run(Map<String, Object> args) {
        EVENT_ARGS convertedArgs = objectMapper.convertValue(args, eventArgsClass);
        log.info("Starting processing of '{}'...", convertedArgs);        
        serviceClient.createEvent("ProcessingStarted", getProcessedEvent(args));
        long start = System.currentTimeMillis();
        handle(convertedArgs);
        serviceClient.createEvent("ProcessingEnded", getProcessedEvent(args));
        log.info("Processing of '{}' ended in {} seconds.", convertedArgs, (System.currentTimeMillis() - start) / 1000);
    }

    private Map<String, Object> getProcessedEvent(Map<String, Object> args) {
        Map<String, Object> processedEvent = new HashMap<>();
        processedEvent.put("name", getSupportedEventName());
        processedEvent.put("args", args);
        return processedEvent;
    }
}
