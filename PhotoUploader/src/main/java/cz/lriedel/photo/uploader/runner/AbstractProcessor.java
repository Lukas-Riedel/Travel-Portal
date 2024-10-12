package cz.lriedel.photo.uploader.runner;

import java.util.Map;
import java.util.Objects;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.databind.ObjectMapper;

abstract class AbstractProcessor<JOB_ARGS> implements Processor {

    protected final Logger logger = LoggerFactory.getLogger(getClass());

    protected final RestTemplate restTemplate;
    protected final ObjectMapper objectMapper;

    private final Class<JOB_ARGS> jobArgsClass;

    protected AbstractProcessor(RestTemplate restTemplate, ObjectMapper objectMapper, Class<JOB_ARGS> jobArgsClass) {
        this.restTemplate = Objects.requireNonNull(restTemplate);
        this.objectMapper = Objects.requireNonNull(objectMapper);
        this.jobArgsClass = Objects.requireNonNull(jobArgsClass);
    }

    @Override
    public final void process(Map<String, Object> args) throws Exception {
        JOB_ARGS convertedArgs = objectMapper.convertValue(args, jobArgsClass);
        logger.info("Starting processing of '{}'...", convertedArgs);
        process(convertedArgs);
    }

    protected abstract void process(JOB_ARGS jobArgs) throws Exception;
}
