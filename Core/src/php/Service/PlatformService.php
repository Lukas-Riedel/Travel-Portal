<?php
    class PlatformService {        
        public function onApplicationStarted(mixed $message) : void {
            global $databaseProvider, $googleApiClient;

            $dump = array();

            foreach (explode(",", $message["tables"]) as &$table) {
                $dump[] = "-- " . $table;
        
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

                $dump[] = "";
            }

            $googleApiClient->createFile("Backup " . date("d.m.Y H:i:s") . ".sql", null, "application/sql", implode("\n", $dump));
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