<?php
    require_once(dirname(__FILE__) . "/../model/TimeTrackingEvent.php");

    class TimeTrackingService {
        public function createTimeTrackingEvent($type, $hours, $description, $date) : TimeTrackingEvent {
            global $databaseProvider;
        
            $databaseProvider
                ->statementBuilder("INSERT INTO tracking (type, hours, description, timestamp) VALUES (?, ?, ?, ?)")
                ->withParameters($type, doubleval($hours), $description, strtotime($date) + 9 * 3600)
                ->execute();
                
            $trackingEventRow = $databaseProvider
                ->statementBuilder("SELECT * FROM tracking ORDER BY id DESC LIMIT 1")
                ->getSingleRow();

            $balance = $databaseProvider
                ->statementBuilder("SELECT SUM(hours) AS balance FROM tracking WHERE timestamp <= ? AND type = ?")
                ->withParameters($trackingEventRow["timestamp"], $trackingEventRow["type"])
                ->getSingleColumn("balance");

            // A little hack to force the trip_summary view materialization before there's a support for propagating dependencies over functions.
            $databaseProvider
                ->statementBuilder("UPDATE view_materialization SET is_materialization_delayed = 1 WHERE view_name = '_trip_summary'")
                ->execute();

            return new TimeTrackingEvent($trackingEventRow["id"], $trackingEventRow["description"], floatval($trackingEventRow["hours"]),
                $trackingEventRow["timestamp"], $trackingEventRow["type"], floatval($balance));
        }

        public function getTimeTrackingEvents($type = NULL) : array {
            global $databaseProvider;
            
            $timeTrackingEvents = array();

            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($type !== NULL) {
                $whereClauseBuilder->withClause("type = ?", $type);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $trackingEventRows = $databaseProvider
                ->statementBuilder("SELECT * FROM tracking {{WHERE CLAUSE}} ORDER BY timestamp DESC, id DESC", $whereClause)
                ->getResultSet();

            foreach ($trackingEventRows as &$trackingEventRow) {
                $whereClauseBuilder = $databaseProvider->whereClauseBuilder()->withClause("timestamp <= ?", $trackingEventRow["timestamp"]);
                if ($type !== NULL) {
                    $whereClauseBuilder->withClause("type = ?", $type);
                }
                $whereClause = $whereClauseBuilder->buildForAnd();

                $balance = $databaseProvider
                    ->statementBuilder("SELECT SUM(hours) AS balance FROM tracking {{WHERE CLAUSE}}", $whereClause)
                    ->getSingleColumn("balance");

                $timeTrackingEvents[] = new TimeTrackingEvent($trackingEventRow["id"], $trackingEventRow["description"], floatval($trackingEventRow["hours"]),
                    $trackingEventRow["timestamp"], $trackingEventRow["type"], floatval($balance));
            }

            return $timeTrackingEvents;
        }

        public function removeTimeTrackingEvent($eventId) : bool {            
            global $databaseProvider;

            $wasDeleted = $databaseProvider
                ->statementBuilder("DELETE FROM tracking WHERE id = ?")
                ->withParameters($eventId)
                ->execute() === 1;

            // A little hack to force the trip_summary view materialization before there's a support for propagating dependencies over functions.
            $databaseProvider
                ->statementBuilder("UPDATE view_materialization SET is_materialization_delayed = 1 WHERE view_name = '_trip_summary'")
                ->execute();

            return $wasDeleted;
        }
    }
?>