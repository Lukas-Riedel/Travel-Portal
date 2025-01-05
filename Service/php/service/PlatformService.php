<?php
    class PlatformService {        
        public function onApplicationStarted($message) {
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
                        $values[] = $value === NULL ? "NULL" : ("'" . str_replace("'", "''", $value) . "'");
                    }

                    $dump[] = "INSERT INTO " . $table . " (" . implode(", ", $keys) . ") VALUES (" . implode(", ", $values) . ");";
                }

                $dump[] = "";
            }

            $googleApiClient->createFile("Backup " . date("d.m.Y H:i:s") . ".sql", NULL, "application/sql", implode("\n", $dump));

            $pruneStatements = $databaseProvider
                ->statementBuilder("SELECT * FROM pruner")
                ->getResultSetForColumn("query");

            foreach ($pruneStatements as &$pruneStatement) {
                $databaseProvider 
                    ->statementBuilder($pruneStatement)
                    ->execute();
            }
        }

        public function onSchedulerTriggered($message) : void {
            global $configuration, $configurationService, $scheduler, $eventPublisher;

            if ($message["action"] === "WATCH_CALENDAR" && $message["timeSinceLastExecution"] > 82800) {
                $watchId = bin2hex(random_bytes(16));
                $configurationService->updateGoogleCalendarWatchId($watchId);
                foreach ($configuration["calendars"] as $calendar => $url) {
                    $eventPublisher->publishCalendarWatchRenewingEvent($calendar, $watchId);
                }

                $scheduler->recordEventsTriggered($message["action"]);
            }
        }
    }
?>