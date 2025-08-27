package cz.lriedel.agent;

import java.util.UUID;
import java.util.concurrent.TimeUnit;

import org.springframework.boot.CommandLineRunner;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

import cz.lriedel.agent.client.ServiceClient;
import cz.lriedel.agent.persistance.Configuration;
import cz.lriedel.agent.persistance.ConfigurationRepository;

@Component
public class AgentApplicationInitializer implements CommandLineRunner {

    private static final String DEVICE_ID_CONFIGURATION_KEY = "deviceId";

    private final ConfigurationRepository configurationRepository;
    private final ServiceClient serviceClient;

    public AgentApplicationInitializer(ConfigurationRepository configurationRepository, ServiceClient serviceClient) {
        this.configurationRepository = configurationRepository;
        this.serviceClient = serviceClient;
    }

    @Scheduled(fixedDelayString = "${device.registration.interval}", timeUnit = TimeUnit.SECONDS)
    public void registerDevice() {
        configurationRepository.findById(DEVICE_ID_CONFIGURATION_KEY).map(Configuration::getValue).ifPresent(serviceClient::registerDevice);
    }


    @Override
    public void run(String... args) throws Exception {
        if (!configurationRepository.existsById(DEVICE_ID_CONFIGURATION_KEY)) {
            configurationRepository.save(new Configuration(DEVICE_ID_CONFIGURATION_KEY, UUID.randomUUID().toString()));
        }
    }
}
