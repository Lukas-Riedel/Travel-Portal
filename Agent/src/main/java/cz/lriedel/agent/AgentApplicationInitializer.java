package cz.lriedel.agent;

import cz.lriedel.agent.client.ServiceClient;
import cz.lriedel.agent.client.UserTokenSupplier;
import cz.lriedel.agent.persistance.Configuration;
import cz.lriedel.agent.persistance.ConfigurationRepository;
import lombok.extern.slf4j.Slf4j;
import org.springframework.boot.CommandLineRunner;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

import java.io.Console;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.TimeUnit;

import static cz.lriedel.agent.persistance.ConfigurationRepository.DEVICE_ID_CONFIGURATION_KEY;

@Slf4j
@Component
public class AgentApplicationInitializer implements CommandLineRunner {

    private static final String QUEUE_NAME_CONFIGURATION_KEY = "queueName";

    private final ConfigurationRepository configurationRepository;
    private final UserTokenSupplier userTokenSupplier;
    private final ServiceClient serviceClient;
    private final List<AgentContextDataProvider> agentContextDataProviders;

    private final String agentQueueName;

    public AgentApplicationInitializer(ConfigurationRepository configurationRepository, ServiceClient serviceClient,
                                       UserTokenSupplier userTokenSupplier, List<AgentContextDataProvider> agentContextDataProviders, String agentQueueName) {
        this.configurationRepository = configurationRepository;
        this.serviceClient = serviceClient;
        this.userTokenSupplier = userTokenSupplier;
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

        serviceClient.registerDevice(agentId, data);
    }

    @Override
    public void run(String... args) {
        if (!isAuthenticated()) {
            Console console = System.console();

            String username = console.readLine("Username: ");
            String password = new String(console.readPassword("Password: "));

            userTokenSupplier.login(username, password);

            log.info("Login was successful!");
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
