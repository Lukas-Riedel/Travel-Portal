package cz.lriedel.agent.job;

import com.fasterxml.jackson.databind.ObjectMapper;
import lombok.extern.slf4j.Slf4j;

import java.util.Map;

@Slf4j
public abstract class AbstractJob<JOB_ARGS> implements Job<JOB_ARGS> {

    protected final ObjectMapper objectMapper;

    private final Class<JOB_ARGS> jobArgsClass;

    protected AbstractJob(ObjectMapper objectMapper, Class<JOB_ARGS> jobArgsClass) {
        this.objectMapper = objectMapper;
        this.jobArgsClass = jobArgsClass;
    }

    public final void run(Map<String, Object> args) {
        JOB_ARGS convertedArgs = objectMapper.convertValue(args, jobArgsClass);
        log.info("Starting processing of '{}'...", convertedArgs);
        long start = System.currentTimeMillis();
        run(convertedArgs);
        log.info("Processing of '{}' ended in {} seconds.", convertedArgs, (System.currentTimeMillis() - start) / 1000);
    }
}
