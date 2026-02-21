package cz.lriedel.agent;

import com.google.common.collect.Iterables;
import cz.lriedel.agent.client.CoreClient;
import cz.lriedel.agent.client.UserTokenSupplier;
import cz.lriedel.agent.persistance.Configuration;
import cz.lriedel.agent.persistance.ConfigurationRepository;
import lombok.extern.slf4j.Slf4j;
import org.springframework.boot.ApplicationArguments;
import org.springframework.boot.ApplicationRunner;
import org.springframework.retry.support.RetryTemplate;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;

import static cz.lriedel.agent.persistance.ConfigurationRepository.DEVICE_ID_CONFIGURATION_KEY;
import static cz.lriedel.agent.persistance.ConfigurationRepository.QUEUE_NAME_CONFIGURATION_KEY;

@Slf4j
@Component
class AgentApplicationInitializer implements ApplicationRunner {

    private static final String USERNAME_ARGUMENT_NAME = "username";
    private static final String PASSWORD_ARGUMENT_NAME = "password";

    private final ConfigurationRepository configurationRepository;
    private final UserTokenSupplier userTokenSupplier;
    private final CoreClient coreClient;
    private final RetryTemplate retryTemplate;
    private final List<AgentContextDataProvider> agentContextDataProviders;

    private final String agentQueueName;

    AgentApplicationInitializer(ConfigurationRepository configurationRepository, CoreClient coreClient, UserTokenSupplier userTokenSupplier,
            RetryTemplate retryTemplate, List<AgentContextDataProvider> agentContextDataProviders, String agentQueueName) {
        this.configurationRepository = configurationRepository;
        this.coreClient = coreClient;
        this.userTokenSupplier = userTokenSupplier;
        this.retryTemplate = retryTemplate;
        this.agentContextDataProviders = agentContextDataProviders;
        this.agentQueueName = agentQueueName;
    }

    @Scheduled(fixedDelayString = "${agent.core.registration.interval}", timeUnit = TimeUnit.SECONDS)
    public void registerDevice() {
        configurationRepository.findById(DEVICE_ID_CONFIGURATION_KEY).map(Configuration::getValue).ifPresent(this::registerDevice);
    }

    private void registerDevice(String agentId) {
        Map<String, Object> data = new HashMap<>();
        data.put(QUEUE_NAME_CONFIGURATION_KEY, agentQueueName);
        for (AgentContextDataProvider agentContextDataProvider : agentContextDataProviders) {
            data.putAll(agentContextDataProvider.getContextData());
        }

        retryTemplate.execute(context -> {
            coreClient.registerDevice(agentId, data);
            return null;
        });
    }

    @Override
    public void run(ApplicationArguments args) {
        if (args.containsOption(USERNAME_ARGUMENT_NAME) && args.containsOption(PASSWORD_ARGUMENT_NAME)) {
            log.info("Logging in with the provided credentials...");

            userTokenSupplier.login(
                    Iterables.getOnlyElement(args.getOptionValues(USERNAME_ARGUMENT_NAME)),
                    Iterables.getOnlyElement(args.getOptionValues(PASSWORD_ARGUMENT_NAME))
            );
        }

        if (!isAuthenticated()) {
            System.exit(1);
        }
    }

    private boolean isAuthenticated() {
        try {
            userTokenSupplier.get();
        }
        catch (Exception e) {
            return false;
        }

        return true;
    }
}
