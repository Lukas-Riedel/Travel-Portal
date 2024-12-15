package cz.lriedel.agent.job;

public interface Job<JOB_ARGS> {

    void run(JOB_ARGS args);
}
