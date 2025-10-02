package cz.lriedel.agent.persistance;

import org.springframework.data.jpa.repository.JpaRepository;

public interface ConfigurationRepository extends JpaRepository<Configuration, String> {
    String DEVICE_ID_CONFIGURATION_KEY = "deviceId";
    String REFRESH_TOKEN_CONFIGURATION_KEY = "refreshToken";
}
