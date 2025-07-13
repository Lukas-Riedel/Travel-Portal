package cz.lriedel.agent.event;

import com.fasterxml.jackson.databind.ObjectMapper;
import lombok.extern.slf4j.Slf4j;

import java.util.Map;

@Slf4j
public abstract class AbstractEventHandler<EVENT_ARGS> implements EventHandler<EVENT_ARGS> {

    protected final ObjectMapper objectMapper;

    private final Class<EVENT_ARGS> eventArgsClass;

    protected AbstractEventHandler(ObjectMapper objectMapper, Class<EVENT_ARGS> eventArgsClass) {
        this.objectMapper = objectMapper;
        this.eventArgsClass = eventArgsClass;
    }

    public final void run(Map<String, Object> args) {
        EVENT_ARGS convertedArgs = objectMapper.convertValue(args, eventArgsClass);
        log.info("Starting processing of '{}'...", convertedArgs);
        long start = System.currentTimeMillis();
        handle(convertedArgs);
        log.info("Processing of '{}' ended in {} seconds.", convertedArgs, (System.currentTimeMillis() - start) / 1000);
    }
}
