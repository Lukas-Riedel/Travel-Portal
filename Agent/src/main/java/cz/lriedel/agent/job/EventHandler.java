package cz.lriedel.agent.job;

public interface EventHandler<EVENT_ARGS> {

    void handle(EVENT_ARGS args);
}
