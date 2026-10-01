<?php
    namespace Core\OpenLineage;

    use Core\Client\Google\GoogleClient;

    class GoogleDriveOpenLineageEventPublisher implements OpenLineageEventPublisher {

        private const OPENLINEAGE_EVENTS_FOLDER_NAME = "OpenLineage Events";

        private readonly GoogleClient $googleClient;

        public function __construct(GoogleClient $googleClient) {
            $this->googleClient = $googleClient;
        }

        public function publishEvent(OpenLineageEvent $event) : void {

            $rootFolderId = $this->googleClient->getOrCreateFolderId(self::OPENLINEAGE_EVENTS_FOLDER_NAME, null);
            $thisMonthFolderId = $this->googleClient->getOrCreateFolderId(date("m/Y"), $rootFolderId);
            $todayFolderId = $this->googleClient->getOrCreateFolderId(date("d.m.Y"), $thisMonthFolderId);
            $this->googleClient->createFileFromString(time(), $todayFolderId, "application/json", json_encode($event, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
?>