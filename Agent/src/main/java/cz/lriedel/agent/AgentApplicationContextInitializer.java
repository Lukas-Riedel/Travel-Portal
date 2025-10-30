package cz.lriedel.agent;

import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.client.HttpEntityProvider;
import cz.lriedel.agent.client.IamClient;
import cz.lriedel.agent.client.ServiceClient;
import org.springframework.boot.web.client.RestTemplateBuilder;
import org.springframework.context.ApplicationContextInitializer;
import org.springframework.context.ConfigurableApplicationContext;
import org.springframework.core.env.ConfigurableEnvironment;
import org.springframework.core.env.MapPropertySource;
import org.springframework.core.env.MutablePropertySources;
import org.springframework.core.env.PropertySource;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.web.client.RestTemplate;

import java.util.Map;
import java.util.Objects;
import java.util.function.Function;
import java.util.function.Supplier;

public class AgentApplicationContextInitializer implements ApplicationContextInitializer<ConfigurableApplicationContext> {

    private static final String USER_CONFIGURATION_PROPERTY_SOURCE = "UserConfigurationPropertySource";
    private static final String INTERNAL_CONFIGURATION_PROPERTY_SOURCE = "InternalConfigurationPropertySource";

    private static final String SERVICE_IAM_URL = "service.iam.url";
    private static final String SERVICE_CORE_URL = "service.core.url";
    private static final String SERVICE_IAM_CLIENT_ID = "service.iam.client.id";
    private static final String SERVICE_IAM_CLIENT_SECRET = "service.iam.client.secret";

    private static final String DEFAULT_USERNAME = "guest";
    private static final String DEFAULT_PASSWORD = "guest";

    private static final String AGENT_CONFIGURATION_KEY = "agent";

    private final ObjectMapper objectMapper = new ObjectMapper();

    @Override
    public void initialize(ConfigurableApplicationContext applicationContext) {
        ConfigurableEnvironment environment = applicationContext.getEnvironment();
        String coreUrl = Objects.requireNonNull(environment.getProperty(SERVICE_CORE_URL));

        RestTemplate iamRestTemplate = new RestTemplateBuilder().rootUri(environment.getProperty(SERVICE_IAM_URL))
                .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE).build();
        IamClient iamClient = new IamClient(iamRestTemplate);

        MutablePropertySources sources = environment.getPropertySources();
        sources.addFirst(createPropertySource(USER_CONFIGURATION_PROPERTY_SOURCE, coreUrl,
                () -> iamClient.createUserToken(DEFAULT_USERNAME, DEFAULT_PASSWORD).accessToken(),
                properties -> (Map<String, Object>) properties.get(AGENT_CONFIGURATION_KEY)));
        sources.addFirst(createPropertySource(INTERNAL_CONFIGURATION_PROPERTY_SOURCE, coreUrl,
                () -> iamClient.createClientToken(environment.getProperty(SERVICE_IAM_CLIENT_ID), environment.getProperty(SERVICE_IAM_CLIENT_SECRET)).accessToken(),
                Function.identity()));
    }

    private PropertySource<?> createPropertySource(String name, String coreBaseUrl, Supplier<String> tokenSupplier,
                                                   Function<Map<String, Object>, Map<String, Object>> propertiesExtractor) {
        RestTemplate coreRestTemplate = new RestTemplateBuilder().rootUri(coreBaseUrl)
                .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE).build();
        ServiceClient serviceClient = new ServiceClient(coreRestTemplate, new RetryTemplate(),
                new HttpEntityProvider(objectMapper, tokenSupplier));

        return new MapPropertySource(name, propertiesExtractor.apply(serviceClient.getConfiguration()));
    }
}
