package cz.lriedel.photo.uploader.processor;

import java.util.Map;
import java.util.Objects;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.databind.ObjectMapper;

public abstract class AbstractProcessor<JOB_ARGS> implements Processor<JOB_ARGS> {

    protected final Logger logger = LoggerFactory.getLogger(getClass());

    protected final RestTemplate restTemplate;
    protected final ObjectMapper objectMapper;

    private final Class<JOB_ARGS> jobArgsClass;

    protected AbstractProcessor(RestTemplate restTemplate, ObjectMapper objectMapper, Class<JOB_ARGS> jobArgsClass) {
        this.restTemplate = Objects.requireNonNull(restTemplate);
        this.objectMapper = Objects.requireNonNull(objectMapper);
        this.jobArgsClass = Objects.requireNonNull(jobArgsClass);
    }

    public final void process(Map<String, Object> args) throws Exception {
        JOB_ARGS convertedArgs = objectMapper.convertValue(args, jobArgsClass);
        logger.info("Starting processing of '{}'...", convertedArgs);
        long start = System.currentTimeMillis();
        process(convertedArgs);
        logger.info("Processing of '{}' ended in {} seconds.", convertedArgs, (System.currentTimeMillis() - start) / 1000);
    }
}
