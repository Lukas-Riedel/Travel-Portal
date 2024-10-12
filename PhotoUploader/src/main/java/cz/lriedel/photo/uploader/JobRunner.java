package cz.lriedel.photo.uploader;

import java.util.Objects;
import java.util.Set;

import org.apache.commons.lang.StringUtils;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import cz.lriedel.photo.uploader.model.Job;
import cz.lriedel.photo.uploader.processor.AbstractProcessor;
import cz.lriedel.photo.uploader.processor.Processor;

@Component
public final class JobRunner {

    private static final Logger LOGGER = LoggerFactory.getLogger(JobRunner.class);

    private static final String GET_JOBS_ENDPOINT = "/api/jobs/%S";
    private static final String DELETE_JOB_ENDPOINT = "/api/jobs/%s";

    private final RestTemplate restTemplate;
    private final Set<? extends AbstractProcessor<?>> processors;

    JobRunner(RestTemplate restTemplate, Set<? extends AbstractProcessor<?>> processors) {
        this.restTemplate = Objects.requireNonNull(restTemplate);
        this.processors = Set.copyOf(processors);
    }

    @Scheduled(fixedDelayString = "${request.interval.retry}")
    public void run() {
        for (AbstractProcessor<?> processor : processors) {
            Job[] jobs = fetchJobs(processor);

            if (jobs != null) {
                for (Job job : jobs) {
                    try {
                        processor.process(job.args());
                    }
                    catch (Exception e) {
                        LOGGER.error("Unknown error occurred when processing '{}'.", job, e);
                    }

                    terminateJob(job);
                }
            }
        }
    }

    private Job[] fetchJobs(Processor processor) {
        String jobName = processor.getClass().getSimpleName().replace(Processor.class.getSimpleName(), StringUtils.EMPTY);
        return restTemplate.getForObject(String.format(GET_JOBS_ENDPOINT, jobName), Job[].class);
    }

    private void terminateJob(Job job) {
        restTemplate.delete(String.format(DELETE_JOB_ENDPOINT, job.id()));
    }
}
