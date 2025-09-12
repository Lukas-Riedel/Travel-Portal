<?php
    namespace Core\OpenLineage;

    class GoogleDriveOpenLineageEventPublisher implements OpenLineageEventPublisher {

        private const OPENLINEAGE_EVENTS_FOLDER_NAME = "OpenLineage Events";

        private readonly \GoogleApiClient $googleApiClient;

        public function __construct(\GoogleApiClient $googleApiClient) {
            $this->googleApiClient = $googleApiClient;
        }

        public function publishEvent(OpenLineageEvent $event) : void {
            $rootFolderId = $this->googleApiClient->getOrCreateFolderId(self::OPENLINEAGE_EVENTS_FOLDER_NAME, null);
            $thisMonthFolderId = $this->googleApiClient->getOrCreateFolderId(date("m/Y"), $rootFolderId);
            $todayFolderId = $this->googleApiClient->getOrCreateFolderId(date("d.m.Y"), $thisMonthFolderId);
            $this->googleApiClient->createFile(time(), $todayFolderId, "application/json", json_encode($event, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
?>