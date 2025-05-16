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

        public function onSchedulerTriggered(mixed $message) : void {
            global $configuration, $configurationService, $scheduler, $eventPublisher;

            if ($message["action"] === "WATCH_CALENDAR" && time() - $message["lastTriggered"] > 82800) {
                foreach ($configuration["calendars"] as $calendar => $url) {
                    $eventPublisher->publishCalendarWatchRenewingEvent($calendar);
                }

                $scheduler->recordEventsTriggered($message["action"]);
            }
        }
    }
?>