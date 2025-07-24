package cz.lriedel.agent.event;

import org.apache.commons.lang3.StringUtils;

public interface EventHandler<EVENT_ARGS> {

    void handle(EVENT_ARGS args);

    default String getSupportedEventName() {
        return getClass().getSimpleName().replace(EventHandler.class.getSimpleName(), StringUtils.EMPTY);
    }
}
