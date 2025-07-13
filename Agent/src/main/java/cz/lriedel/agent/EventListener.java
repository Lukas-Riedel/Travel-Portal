package cz.lriedel.agent;

import java.util.Set;

import org.apache.commons.lang3.StringUtils;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

import com.fasterxml.jackson.core.JsonProcessingException;

import cz.lriedel.agent.client.ServiceClient;
import cz.lriedel.agent.event.AbstractEventHandler;
import cz.lriedel.agent.event.EventHandler;
import cz.lriedel.agent.model.Event;

@Component
final class EventListener {

    private static final Logger LOGGER = LoggerFactory.getLogger(EventListener.class);

    private final ServiceClient serviceClient;
    private final Set<? extends AbstractEventHandler<?>> eventHandlers;

    EventListener(ServiceClient serviceClient, Set<? extends AbstractEventHandler<?>> eventHandlers) {
        this.serviceClient = serviceClient;
        this.eventHandlers = eventHandlers;
    }

    @Scheduled(fixedDelayString = "${request.interval.retry}")
    public void run() throws JsonProcessingException {
        for (AbstractEventHandler<?> eventHandler : eventHandlers) {
            for (Event event : serviceClient.listEvents(getEventName(eventHandler))) {
                try {
                    eventHandler.run(event.args());
                }
                catch (Exception e) {
                    LOGGER.error("Unknown error occurred when processing '{}'.", event, e);
                }
                finally {
                    serviceClient.removeEvent(event.id());
                }
            }
        }
    }

    private static String getEventName(EventHandler<?> eventHandler) {
        return eventHandler.getClass().getSimpleName().replace(EventHandler.class.getSimpleName(), StringUtils.EMPTY);
    }
}
