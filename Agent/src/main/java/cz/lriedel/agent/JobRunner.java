package cz.lriedel.agent;

import com.fasterxml.jackson.core.JsonProcessingException;
import cz.lriedel.agent.client.ServiceClient;
import cz.lriedel.agent.job.AbstractJob;
import cz.lriedel.agent.job.Job;
import org.apache.commons.lang3.StringUtils;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

import java.util.Set;

@Component
final class JobRunner {

    private static final Logger LOGGER = LoggerFactory.getLogger(JobRunner.class);

    private final ServiceClient serviceClient;
    private final Set<? extends AbstractJob<?>> jobs;

    JobRunner(ServiceClient serviceClient, Set<? extends AbstractJob<?>> jobs) {
        this.serviceClient = serviceClient;
        this.jobs = jobs;
    }

    @Scheduled(fixedDelayString = "${request.interval.retry}")
    public void run() throws JsonProcessingException {
        for (AbstractJob<?> job : jobs) {
            for (cz.lriedel.agent.model.Job jobData : serviceClient.listJobs(getJobName(job))) {
                try {
                    job.run(jobData.args());
                }
                catch (Exception e) {
                    LOGGER.error("Unknown error occurred when processing '{}'.", job, e);
                }
                serviceClient.deleteJob(jobData.id());
            }
        }
    }

    private static String getJobName(Job<?> job) {
        return job.getClass().getSimpleName().replace(Job.class.getSimpleName(), StringUtils.EMPTY);
    }
}
