package cz.lriedel.agent;

import com.fasterxml.jackson.databind.ObjectMapper;
import cz.lriedel.agent.client.CoreClient;
import cz.lriedel.agent.client.HttpEntityProvider;
import cz.lriedel.agent.client.IamClient;
import org.springframework.boot.web.client.RestTemplateBuilder;
import org.springframework.context.ApplicationContextInitializer;
import org.springframework.context.ConfigurableApplicationContext;
import org.springframework.core.env.ConfigurableEnvironment;
import org.springframework.core.env.MapPropertySource;
import org.springframework.core.env.MutablePropertySources;
import org.springframework.core.env.PropertySource;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.web.client.RestTemplate;
import org.springframework.web.util.DefaultUriBuilderFactory;
import org.springframework.web.util.UriBuilderFactory;

import java.util.Map;
import java.util.Objects;
import java.util.function.Supplier;
import java.util.function.UnaryOperator;

public class AgentApplicationContextInitializer implements ApplicationContextInitializer<ConfigurableApplicationContext> {

    private static final String USER_CONFIGURATION_PROPERTY_SOURCE = "UserConfigurationPropertySource";
    private static final String INTERNAL_CONFIGURATION_PROPERTY_SOURCE = "InternalConfigurationPropertySource";

    private static final String SERVICE_IAM_URL_PROPERTY_PLACEHOLDER = "service.iam.url";
    private static final String SERVICE_CORE_URL_PROPERTY_PLACEHOLDER = "service.core.url";
    private static final String SERVICE_IAM_CLIENT_ID_PROPERTY_PLACEHOLDER = "service.iam.client.id";
    private static final String SERVICE_IAM_CLIENT_SECRET_PROPERTY_PLACEHOLDER = "service.iam.client.secret";

    private static final String DEFAULT_USERNAME = "guest";
    private static final String DEFAULT_PASSWORD = "guest";

    private static final String AGENT_CONFIGURATION_KEY = "agent";

    private final LoggingContext loggingContext = new LoggingContext();
    private final ObjectMapper objectMapper = new ObjectMapper();
    private final UriBuilderFactory uriBuilderFactory = new DefaultUriBuilderFactory();

    @Override
    public void initialize(ConfigurableApplicationContext applicationContext) {
        ConfigurableEnvironment environment = applicationContext.getEnvironment();

        String coreUrl = Objects.requireNonNull(environment.getProperty(SERVICE_CORE_URL_PROPERTY_PLACEHOLDER), "The Core URL cannot be null.");
        String iamUrl = Objects.requireNonNull(environment.getProperty(SERVICE_IAM_URL_PROPERTY_PLACEHOLDER), "The IAM URL cannot be null.");

        String agentClientId = Objects.requireNonNull(environment.getProperty(SERVICE_IAM_CLIENT_ID_PROPERTY_PLACEHOLDER),
                "The Agent Client ID cannot be null.");
        String agentClientSecret = Objects.requireNonNull(environment.getProperty(SERVICE_IAM_CLIENT_SECRET_PROPERTY_PLACEHOLDER),
                "The Agent Client Secret cannot be null.");

        RestTemplate iamRestTemplate = new RestTemplateBuilder().rootUri(iamUrl)
                .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE).build();
        IamClient iamClient = new IamClient(iamRestTemplate, objectMapper);

        MutablePropertySources sources = environment.getPropertySources();
        sources.addFirst(createPropertySource(USER_CONFIGURATION_PROPERTY_SOURCE, coreUrl,
                () -> iamClient.createUserToken(DEFAULT_USERNAME, DEFAULT_PASSWORD).accessToken(),
                properties -> (Map<String, Object>) properties.get(AGENT_CONFIGURATION_KEY)));
        sources.addFirst(createPropertySource(INTERNAL_CONFIGURATION_PROPERTY_SOURCE, coreUrl,
                () -> iamClient.createClientToken(agentClientId, agentClientSecret).accessToken(), UnaryOperator.identity()));
    }

    private PropertySource<?> createPropertySource(String name, String coreBaseUrl, Supplier<String> tokenSupplier,
            UnaryOperator<Map<String, Object>> propertiesExtractor) {
        RestTemplate coreRestTemplate = new RestTemplateBuilder().rootUri(coreBaseUrl)
                .defaultHeader(HttpHeaders.CONTENT_TYPE, MediaType.APPLICATION_JSON_VALUE).build();
        CoreClient coreClient = new CoreClient(coreRestTemplate, new HttpEntityProvider(objectMapper, loggingContext, tokenSupplier),
                uriBuilderFactory);

        return new MapPropertySource(name, propertiesExtractor.apply(coreClient.getConfiguration()));
    }
}
