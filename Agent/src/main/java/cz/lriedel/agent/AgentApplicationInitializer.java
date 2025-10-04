package cz.lriedel.agent;

import java.io.Console;
import java.util.Map;
import java.util.concurrent.TimeUnit;

import cz.lriedel.agent.client.AccessTokenProvider;
import lombok.extern.slf4j.Slf4j;
import org.springframework.boot.CommandLineRunner;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

import cz.lriedel.agent.client.ServiceClient;
import cz.lriedel.agent.persistance.Configuration;
import cz.lriedel.agent.persistance.ConfigurationRepository;

import static cz.lriedel.agent.persistance.ConfigurationRepository.DEVICE_ID_CONFIGURATION_KEY;

@Slf4j
@Component
public class AgentApplicationInitializer implements CommandLineRunner {

    private static final String QUEUE_NAME_CONFIGURATION_KEY = "queueName";

    private final ConfigurationRepository configurationRepository;
    private final AccessTokenProvider accessTokenProvider;
    private final ServiceClient serviceClient;

    private final String agentQueueName;

    public AgentApplicationInitializer(ConfigurationRepository configurationRepository, ServiceClient serviceClient,
                                       AccessTokenProvider accessTokenProvider, String agentQueueName) {
        this.configurationRepository = configurationRepository;
        this.serviceClient = serviceClient;
        this.accessTokenProvider = accessTokenProvider;
        this.agentQueueName = agentQueueName;
    }

    @Scheduled(fixedDelayString = "${device.registration.interval}", timeUnit = TimeUnit.SECONDS)
    public void registerDevice() {
        configurationRepository.findById(DEVICE_ID_CONFIGURATION_KEY).map(Configuration::getValue).ifPresent(this::registerDevice);
    }

    private void registerDevice(String agentId) {
        serviceClient.registerDevice(agentId, Map.of(QUEUE_NAME_CONFIGURATION_KEY, agentQueueName));
    }

    @Override
    public void run(String... args) {
        if (!isAuthenticated()) {
            Console console = System.console();

            String username = console.readLine("Username: ");
            String password = new String(console.readPassword("Password: "));

            accessTokenProvider.login(username, password);

            log.info("Login was successful!");
        }
    }

    private boolean isAuthenticated() {
        try {
            accessTokenProvider.getAccessToken();
        }
        catch (Exception e) {
            return false;
        }

        return true;
    }
}
