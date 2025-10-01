package cz.lriedel.agent;

import org.apache.commons.imaging.formats.jpeg.exif.ExifRewriter;
import org.springframework.amqp.core.Queue;
import org.springframework.amqp.core.QueueBuilder;
import org.springframework.amqp.rabbit.annotation.EnableRabbit;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.boot.web.client.RestTemplateBuilder;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.ComponentScan;
import org.springframework.context.annotation.Configuration;
import org.springframework.context.annotation.EnableAspectJAutoProxy;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.retry.annotation.EnableRetry;
import org.springframework.retry.backoff.ExponentialBackOffPolicy;
import org.springframework.retry.policy.SimpleRetryPolicy;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.scheduling.annotation.EnableScheduling;
import org.springframework.web.client.RestTemplate;

import com.fasterxml.jackson.databind.ObjectMapper;

@EnableRabbit
@EnableRetry
@EnableAspectJAutoProxy
@EnableScheduling
@ComponentScan
@Configuration
public class AgentApplicationConfiguration {

    public static final String CORE_SERVICE_QUALIFIER = "core";
    public static final String IAM_SERVICE_QUALIFIER = "iam";

    @Bean
    public ObjectMapper objectMapper() {
        return new ObjectMapper();
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
    public Queue agentQueue(@Value("${queue.agent.name}") String agentQueueName) {
        return QueueBuilder.durable(agentQueueName).build();
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
    public ExifRewriter exifRewriter() {
        return new ExifRewriter();
    }
}
