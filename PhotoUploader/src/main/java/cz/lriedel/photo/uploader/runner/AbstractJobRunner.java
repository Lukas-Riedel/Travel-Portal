package cz.lriedel.photo.uploader.runner;

import java.io.IOException;
import java.util.Objects;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.databind.ObjectMapper;

import cz.lriedel.photo.uploader.model.Job;

abstract class AbstractJobRunner<JOB_ARGS> {

    private static final Logger LOGGER = LoggerFactory.getLogger(AbstractJobRunner.class);

    private static final String GET_JOBS_ENDPOINT = "/api/jobs/%S";
    private static final String DELETE_JOB_ENDPOINT = "/api/jobs/%s";

    protected final RestTemplate restTemplate;
    protected final ObjectMapper objectMapper;

    private final String getJobUrl;
    private final Class<JOB_ARGS> jobArgsClass;

    protected AbstractJobRunner(RestTemplate restTemplate, ObjectMapper objectMapper, String jobName,
        Class<JOB_ARGS> jobArgsClass) {
        this.restTemplate = Objects.requireNonNull(restTemplate);
        this.objectMapper = Objects.requireNonNull(objectMapper);
        this.getJobUrl = String.format(GET_JOBS_ENDPOINT, jobName);
        this.jobArgsClass = Objects.requireNonNull(jobArgsClass);
    }

    @Scheduled(fixedDelayString = "${request.interval.retry}")
    public final void run() {
        Job[] jobs = restTemplate.getForObject(getJobUrl, Job[].class);

        if (jobs != null) {
            for (Job job : jobs) {
                try {
                    process(objectMapper.convertValue(job.args(), jobArgsClass));
                }
                catch (Exception e) {
                    LOGGER.error("Unknown error occurred when processing '{}'.", job, e);
                }
                restTemplate.delete(String.format(DELETE_JOB_ENDPOINT, job.id()));
            }
        }
    }

    protected abstract void process(JOB_ARGS jobArgs) throws IOException, InterruptedException;
}
