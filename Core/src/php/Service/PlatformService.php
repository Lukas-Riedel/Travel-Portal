<?php
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    
    class PlatformService {        
        private const BACKUP_FOLDER_NAME = "Travel Portal Backups";

        public function onApplicationStarted(mixed $message) : void {
            global $databaseClient, $googleApiClient;

            $rootBackupFolderId = $googleApiClient->getOrCreateFolderId(self::BACKUP_FOLDER_NAME, null);
            $backupFolderId = $googleApiClient->createFolder(date("d.m.Y H:i:s"), $rootBackupFolderId);

            foreach (explode(",", $message["tables"]) as &$table) {
                $dump = array();

                $rows = $databaseClient
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
                foreach (\Calendar::cases() as $calendar) {
                    $eventPublisher->publish(Event::CalendarWatchRenewing($calendar->value)); 
                }
            }
        }
    }
?>