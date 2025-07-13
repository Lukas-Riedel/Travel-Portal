package cz.lriedel.agent.event;

public interface EventHandler<EVENT_ARGS> {

    void handle(EVENT_ARGS args);
}
