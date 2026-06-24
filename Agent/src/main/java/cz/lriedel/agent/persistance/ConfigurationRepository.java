package cz.lriedel.agent.persistance;

import org.springframework.data.jpa.repository.JpaRepository;

public interface ConfigurationRepository extends JpaRepository<Configuration, String> {

    String DEVICE_ID_CONFIGURATION_KEY = "deviceId";
    String REFRESH_TOKEN_CONFIGURATION_KEY = "refreshToken";
    String SYNCHRONIZED_FOLDERS_CONFIGURATION_KEY = "synchronizedFolders";
    String DEFAULT_PHOTO_FOLDER_CONFIGURATION_KEY = "defaultPhotoFolder";
    String QUEUE_NAME_CONFIGURATION_KEY = "queueName";
}
