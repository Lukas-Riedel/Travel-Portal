package cz.lriedel.agent;

import java.io.Console;
import java.util.UUID;
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

    private final ConfigurationRepository configurationRepository;
    private final AccessTokenProvider accessTokenProvider;
    private final ServiceClient serviceClient;

    public AgentApplicationInitializer(ConfigurationRepository configurationRepository, ServiceClient serviceClient, AccessTokenProvider accessTokenProvider) {
        this.configurationRepository = configurationRepository;
        this.serviceClient = serviceClient;
        this.accessTokenProvider = accessTokenProvider;
    }

    @Scheduled(fixedDelayString = "${device.registration.interval}", timeUnit = TimeUnit.SECONDS)
    public void registerDevice() {
        configurationRepository.findById(DEVICE_ID_CONFIGURATION_KEY).map(Configuration::getValue).ifPresent(serviceClient::registerDevice);
    }

    @Override
    public void run(String... args) {
        if (!isInitialized()) {
            configurationRepository.save(new Configuration(DEVICE_ID_CONFIGURATION_KEY, UUID.randomUUID().toString()));
        }

        if (!isAuthenticated()) {
            Console console = System.console();

            String username = console.readLine("Username: ");
            String password = new String(console.readPassword("Password: "));

            accessTokenProvider.login(username, password);

            log.info("Login was successful!");
        }
    }

    private boolean isInitialized() {
        return configurationRepository.existsById(DEVICE_ID_CONFIGURATION_KEY);
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
