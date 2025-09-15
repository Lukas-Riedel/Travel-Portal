<?php
    namespace Core\OpenLineage;

    use Core\Service\Configuration\ConfigurationService;

    class GoogleDriveOpenLineageEventPublisher implements OpenLineageEventPublisher {

        private const OPENLINEAGE_EVENTS_FOLDER_NAME = "OpenLineage Events";

        private readonly ConfigurationService $configurationService;

        private readonly \GoogleApiClient $googleApiClient;

        public function __construct(ConfigurationService $configurationService, \GoogleApiClient $googleApiClient) {
            $this->configurationService = $configurationService;
            $this->googleApiClient = $googleApiClient;
        }

        public function publishEvent(OpenLineageEvent $event) : void {
            if (!$this->configurationService->getConfigurationEntry("openLineage")["googleDrive"]["enabled"]) {
                return;
            }

            $rootFolderId = $this->googleApiClient->getOrCreateFolderId(self::OPENLINEAGE_EVENTS_FOLDER_NAME, null);
            $thisMonthFolderId = $this->googleApiClient->getOrCreateFolderId(date("m/Y"), $rootFolderId);
            $todayFolderId = $this->googleApiClient->getOrCreateFolderId(date("d.m.Y"), $thisMonthFolderId);
            $this->googleApiClient->createFile(time(), $todayFolderId, "application/json", json_encode($event, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
?>