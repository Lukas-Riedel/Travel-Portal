<?php
    namespace Core\OpenLineage;

    use Core\Service\Configuration\ConfigurationService;
    use Core\Client\Google\GoogleClient;

    class GoogleDriveOpenLineageEventPublisher implements OpenLineageEventPublisher {

        private const OPENLINEAGE_EVENTS_FOLDER_NAME = "OpenLineage Events";

        private readonly ConfigurationService $configurationService;
        private readonly GoogleClient $googleClient;

        public function __construct(ConfigurationService $configurationService, GoogleClient $googleClient) {
            $this->configurationService = $configurationService;
            $this->googleClient = $googleClient;
        }

        public function publishEvent(OpenLineageEvent $event) : void {
            if (!$this->configurationService->getConfigurationEntry("openLineage")["producers"]["googleDrive"]["enabled"]) {
                return;
            }

            $rootFolderId = $this->googleClient->getOrCreateFolderId(self::OPENLINEAGE_EVENTS_FOLDER_NAME, null);
            $thisMonthFolderId = $this->googleClient->getOrCreateFolderId(date("m/Y"), $rootFolderId);
            $todayFolderId = $this->googleClient->getOrCreateFolderId(date("d.m.Y"), $thisMonthFolderId);
            $this->googleClient->createFile(time(), $todayFolderId, "application/json", json_encode($event, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
?>