package cz.lriedel.agent;

import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.datatype.jsr310.JavaTimeModule;
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
import org.springframework.web.util.DefaultUriBuilderFactory;
import org.springframework.web.util.UriBuilderFactory;

import java.time.Duration;
import java.util.UUID;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

import static cz.lriedel.agent.LoggingContext.TRANSACTION_ID_HEADER;
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
    public static ExecutorService executorService(@Value("${agent.core.workers}") int availableWorkers) {
        return Executors.newFixedThreadPool(availableWorkers);
    }

    @Bean
    public static ObjectMapper objectMapper() {
        return new ObjectMapper().registerModule(new JavaTimeModule());
    }

    @Bean
    @Qualifier(CORE_SERVICE_QUALIFIER)
    public static UriBuilderFactory coreUriBuilderFactory(@Value("${service.core.url}") String serviceUrl) {
        return new DefaultUriBuilderFactory(serviceUrl);
    }

    @Bean
    @Qualifier(CORE_SERVICE_QUALIFIER)
    public static RestTemplate coreRestTemplate(@Value("${service.core.url}") String serviceUrl) {
        return new RestTemplateBuilder()
            .rootUri(serviceUrl)
            .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE)
            .connectTimeout(Duration.ofSeconds(5))
            .readTimeout(Duration.ofSeconds(30))
            .build();
    }

    @Bean
    @Qualifier(IAM_SERVICE_QUALIFIER)
    public static RestTemplate iamRestTemplate(@Value("${service.iam.url}") String serviceUrl) {
        return new RestTemplateBuilder()
            .rootUri(serviceUrl)
            .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE)
            .connectTimeout(Duration.ofSeconds(5))
            .readTimeout(Duration.ofSeconds(10))
            .build();
    }

    @Bean
    public static ThreadPoolTaskScheduler threadPoolTaskScheduler(@Value("${agent.core.scheduler.thread.count:4}") int threadCount) {
        ThreadPoolTaskScheduler scheduler = new ThreadPoolTaskScheduler();
        scheduler.setPoolSize(threadCount);
        scheduler.initialize();
        return scheduler;
    }

    @Bean
    public static Queue agentQueue(String agentQueueName) {
        return QueueBuilder.nonDurable(agentQueueName).autoDelete().build();
    }

    @Bean
    public static RetryTemplate retryTemplate(@Value("${agent.core.retry.attempts:5}") int maxAttempts,
            @Value("${agent.core.retry.interval:2000}") long initialInterval, @Value("${agent.core.retry.multiplier:2}") int backoffMultiplier) {
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
    public static String agentQueueName(@Value("${agent.core.queue.name}") String agentQueuePrefix, String agentIdentifier) {
        return String.join("_", agentQueuePrefix, agentIdentifier);
    }

    @Bean
    public static String agentIdentifier(ConfigurationRepository configurationRepository) {
        if (!configurationRepository.existsById(DEVICE_ID_CONFIGURATION_KEY)) {
            configurationRepository.save(new Configuration(DEVICE_ID_CONFIGURATION_KEY, UUID.randomUUID().toString()));
        }
        return configurationRepository.findById(DEVICE_ID_CONFIGURATION_KEY).map(Configuration::getValue).orElseThrow();
    }

    @Bean
    public static BeanPostProcessor beanPostProcessor(LoggingContext loggingContext) {
        return new BeanPostProcessor() {

            @Override
            public Object postProcessAfterInitialization(Object bean, String beanName) throws BeansException {
                if (bean instanceof AbstractRabbitListenerContainerFactory<?> factory) {
                    MessagePostProcessor messagePostProcessor = message -> {
                        MessageProperties messageProperties = message.getMessageProperties();
                        String transactionId = messageProperties.getHeader(TRANSACTION_ID_HEADER);
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
    public static ExifRewriter exifRewriter() {
        return new ExifRewriter();
    }
}
