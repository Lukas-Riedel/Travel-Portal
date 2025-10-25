package cz.lriedel.agent;

import static cz.lriedel.agent.persistance.ConfigurationRepository.DEVICE_ID_CONFIGURATION_KEY;

import java.util.UUID;

import org.apache.commons.imaging.formats.jpeg.exif.ExifRewriter;
import org.springframework.amqp.core.Queue;
import org.springframework.amqp.core.QueueBuilder;
import org.springframework.amqp.rabbit.annotation.EnableRabbit;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.web.client.RestTemplateBuilder;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.ComponentScan;
import org.springframework.context.annotation.EnableAspectJAutoProxy;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.retry.annotation.EnableRetry;
import org.springframework.retry.backoff.ExponentialBackOffPolicy;
import org.springframework.retry.policy.SimpleRetryPolicy;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.scheduling.annotation.EnableScheduling;
import org.springframework.scheduling.concurrent.ThreadPoolTaskScheduler;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.datatype.jsr310.JavaTimeModule;

import cz.lriedel.agent.persistance.Configuration;
import cz.lriedel.agent.persistance.ConfigurationRepository;

@EnableRabbit
@EnableRetry
@EnableAspectJAutoProxy
@EnableScheduling
@ComponentScan
@org.springframework.context.annotation.Configuration
public class AgentApplicationConfiguration {

    public static final String CORE_SERVICE_QUALIFIER = "core";
    public static final String IAM_SERVICE_QUALIFIER = "iam";

    @Bean
    public ObjectMapper objectMapper() {
        return new ObjectMapper().registerModule(new JavaTimeModule());
    }

    @Bean
    @Qualifier(CORE_SERVICE_QUALIFIER)
    public RestTemplate coreRestTemplate(@Value("${service.core.url}") String serviceUrl) {
        return new RestTemplateBuilder()
            .rootUri(serviceUrl)
            .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE)
            .build();
    }

    @Bean
    @Qualifier(IAM_SERVICE_QUALIFIER)
    public RestTemplate iamRestTemplate(@Value("${service.iam.url}") String serviceUrl) {
        return new RestTemplateBuilder()
            .rootUri(serviceUrl)
            .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE)
            .build();
    }

    @Bean
    public ThreadPoolTaskScheduler threadPoolTaskScheduler(@Value("${scheduler.thread.count:4}") int threadCount) {
        ThreadPoolTaskScheduler scheduler = new ThreadPoolTaskScheduler();
        scheduler.setPoolSize(threadCount);
        scheduler.initialize();
        return scheduler;
    }

    @Bean
    public Queue agentQueue(String agentQueueName) {
        return QueueBuilder.nonDurable(agentQueueName).autoDelete().build();
    }

    @Bean
    public RetryTemplate retryTemplate(@Value("${retry.maxAttempts:5}") int maxAttempts,
        @Value("${retry.initialInterval:2000}") long initialInterval,
        @Value("${retry.backoffMultiplier:2}") int backoffMultiplier) {
        RetryTemplate retryTemplate = new RetryTemplate();

        SimpleRetryPolicy retryPolicy = new SimpleRetryPolicy(maxAttempts);
        retryTemplate.setRetryPolicy(retryPolicy);

        ExponentialBackOffPolicy backOffPolicy = new ExponentialBackOffPolicy();
        backOffPolicy.setInitialInterval(initialInterval);
        backOffPolicy.setMultiplier(backoffMultiplier);
        retryTemplate.setBackOffPolicy(backOffPolicy);

        return retryTemplate;
    }

    @Bean
    public String agentQueueName(@Value("${queue.agent.prefix}") String agentQueuePrefix, String agentIdentifier) {
        return agentQueuePrefix + "_" + agentIdentifier;
    }

    @Bean
    public String agentIdentifier(ConfigurationRepository configurationRepository) {
        if (!configurationRepository.existsById(DEVICE_ID_CONFIGURATION_KEY)) {
            configurationRepository.save(new Configuration(DEVICE_ID_CONFIGURATION_KEY, UUID.randomUUID().toString()));
        }
        return configurationRepository.findById(DEVICE_ID_CONFIGURATION_KEY).map(Configuration::getValue).orElseThrow();
    }

    @Bean
    public ExifRewriter exifRewriter() {
        return new ExifRewriter();
    }
}
