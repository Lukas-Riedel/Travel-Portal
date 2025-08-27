<?php
    class PlatformService {        
        private const BACKUP_FOLDER_NAME = "Travel Portal Backups";

        public function onApplicationStarted(mixed $message) : void {
            global $databaseProvider, $googleApiClient;

            $rootBackupFolder = $googleApiClient->getFolder(self::BACKUP_FOLDER_NAME, null);
            $rootBackupFolderId = $rootBackupFolder === null ? $googleApiClient->createFolder(self::BACKUP_FOLDER_NAME, null) : $rootBackupFolder["id"];

            $backupFolderId = $googleApiClient->createFolder(date("d.m.Y H:i:s"), $rootBackupFolderId);

            foreach (explode(",", $message["tables"]) as &$table) {
                $dump = array();

                $rows = $databaseProvider
                    ->statementBuilder("SELECT * FROM " . $table)
                    ->getResultSet();

                foreach ($rows as &$row) {
                    $keys = array();
                    $values = array();

                    foreach ($row as $key => $value) {
                        $keys[] = "`" . $key . "`";
                        $values[] = $value === null ? "null" : ("'" . str_replace("'", "''", $value) . "'");
                    }

                    $dump[] = "INSERT INTO " . $table . " (" . implode(", ", $keys) . ") VALUES (" . implode(", ", $values) . ");";
                }

                $googleApiClient->createFile($table . ".sql", $backupFolderId, "application/sql", implode("\n", $dump));
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            global $configurationService, $scheduler, $eventPublisher;

            if ($scheduler->requestExecution("WATCH_CALENDAR", 82800)) {
                foreach (array_keys($configurationService->getConfigurationEntry("calendars")) as $calendar) {
                    $eventPublisher->publishCalendarWatchRenewingEvent($calendar); 
                }
            }
        }
    }
?>