package cz.lriedel.photo.uploader;

import com.fasterxml.jackson.core.JsonProcessingException;
import cz.lriedel.photo.uploader.model.Job;
import cz.lriedel.photo.uploader.processor.AbstractProcessor;
import cz.lriedel.photo.uploader.processor.Processor;
import org.apache.commons.lang.StringUtils;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.HttpMethod;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;
import org.springframework.web.client.RestTemplate;

import java.util.Objects;
import java.util.Set;

@Component
public final class JobRunner {

    private static final Logger LOGGER = LoggerFactory.getLogger(JobRunner.class);

    private static final String GET_JOBS_ENDPOINT = "/api/jobs/%S";
    private static final String DELETE_JOB_ENDPOINT = "/api/jobs/%s";

    private final RestTemplate restTemplate;
    private final RetryTemplate retryTemplate;
    private final HttpEntityProvider httpEntityProvider;
    private final Set<? extends AbstractProcessor<?>> processors;

    JobRunner(RestTemplate restTemplate, RetryTemplate retryTemplate, HttpEntityProvider httpEntityProvider,
              Set<? extends AbstractProcessor<?>> processors) {
        this.restTemplate = Objects.requireNonNull(restTemplate);
        this.retryTemplate = Objects.requireNonNull(retryTemplate);
        this.httpEntityProvider = Objects.requireNonNull(httpEntityProvider);
        this.processors = Set.copyOf(processors);
    }

    @Scheduled(fixedDelayString = "${request.interval.retry}")
    public void run() throws JsonProcessingException {
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

    private Job[] fetchJobs(Processor<?> processor) {
        String jobName = processor.getClass().getSimpleName().replace(Processor.class.getSimpleName(), StringUtils.EMPTY);
        return retryTemplate.execute(context -> restTemplate.exchange(String.format(GET_JOBS_ENDPOINT, jobName),
                        HttpMethod.GET, httpEntityProvider.getEmptyHttpEntity(), Job[].class).getBody());
    }

    private void terminateJob(Job job) {
        retryTemplate.execute(context -> restTemplate.exchange(String.format(DELETE_JOB_ENDPOINT, job.id()),
                HttpMethod.DELETE, httpEntityProvider.getEmptyHttpEntity(), Void.class));
    }
}
