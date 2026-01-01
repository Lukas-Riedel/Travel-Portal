package cz.lriedel.agent;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.datatype.jsr310.JavaTimeModule;
import cz.lriedel.agent.client.HttpEntityProvider;
import cz.lriedel.agent.client.ServiceClient;
import cz.lriedel.agent.persistance.Configuration;
import cz.lriedel.agent.persistance.ConfigurationRepository;
import org.apache.commons.imaging.formats.jpeg.exif.ExifRewriter;
import org.apache.commons.lang3.StringUtils;
import org.springframework.amqp.core.MessagePostProcessor;
import org.springframework.amqp.core.MessageProperties;
import org.springframework.amqp.core.Queue;
import org.springframework.amqp.core.QueueBuilder;
import org.springframework.amqp.rabbit.annotation.EnableRabbit;
import org.springframework.amqp.rabbit.config.AbstractRabbitListenerContainerFactory;
import org.springframework.beans.BeansException;
import org.springframework.beans.factory.annotation.Qualifier;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.beans.factory.config.BeanPostProcessor;
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

import java.util.UUID;

import static cz.lriedel.agent.persistance.ConfigurationRepository.DEVICE_ID_CONFIGURATION_KEY;

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
    public ServiceClient serviceClient(@Qualifier(CORE_SERVICE_QUALIFIER) RestTemplate restTemplate, RetryTemplate retryTemplate,
                                       HttpEntityProvider httpEntityProvider) {
        return new ServiceClient(restTemplate, retryTemplate, httpEntityProvider);
    }

    @Bean
    public ThreadPoolTaskScheduler threadPoolTaskScheduler(@Value("${agent.core.scheduler.thread.count:4}") int threadCount) {
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
    public RetryTemplate retryTemplate(@Value("${agent.core.retry.attempts:5}") int maxAttempts,
        @Value("${agent.core.retry.interval:2000}") long initialInterval,
        @Value("${agent.core.retry.multiplier:2}") int backoffMultiplier) {
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
    public String agentQueueName(@Value("${agent.core.queue.name}") String agentQueuePrefix, String agentIdentifier) {
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
    public BeanPostProcessor beanPostProcessor(LoggingContext loggingContext) {
        return new BeanPostProcessor() {

            @Override
            public Object postProcessAfterInitialization(Object bean, String beanName) throws BeansException {
                if (bean instanceof AbstractRabbitListenerContainerFactory<?> factory) {
                    MessagePostProcessor messagePostProcessor = message -> {
                        MessageProperties messageProperties = message.getMessageProperties();
                        String transactionId = messageProperties.getHeader(LoggingContext.TRANSACTION_ID_HEADER);
                        if (StringUtils.isNotBlank(transactionId)) {
                            loggingContext.setTransactionId(transactionId);
                        }
                        return message;
                    };
                    factory.setAfterReceivePostProcessors(messagePostProcessor);
                }
                return bean;
            }
        };
    }

    @Bean
    public ExifRewriter exifRewriter() {
        return new ExifRewriter();
    }
}
