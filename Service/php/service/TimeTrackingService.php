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

        public function resetOpeningBalances() {
            global $configuration, $databaseProvider;

            foreach ($configuration["timeOffHours"] as $eventType => $openingBalance) {
                $carryOverBalance = $databaseProvider
                    ->statementBuilder("SELECT SUM(hours) AS balance FROM tracking WHERE type = ? AND YEAR(FROM_UNIXTIME(timestamp)) < YEAR(FROM_UNIXTIME(UNIX_TIMESTAMP()))")
                    ->withParameters($eventType)
                    ->getSingleColumn("balance");

                $wasReset = $databaseProvider
                    ->statementBuilder("DELETE FROM tracking WHERE type = ? AND YEAR(FROM_UNIXTIME(timestamp)) < YEAR(FROM_UNIXTIME(UNIX_TIMESTAMP()))")
                    ->withParameters($eventType)
                    ->execute() > 0;

                if ($wasReset) {    
                    if ($carryOverBalance !== NULL && $carryOverBalance > 0) {
                        $this->createTimeTrackingEvent($eventType, $carryOverBalance, "Carried over from last year", "1.1." . date("Y"));
                    }
                    
                    if ($openingBalance > 0) {
                        $this->createTimeTrackingEvent($eventType, $openingBalance, "Opening balance", "1.1." . date("Y"));
                    }
                }
            }
        }

        public function onVacationReset($message) : void {
            $this->resetOpeningBalances();
        }

        public function onSchedulerTriggered($message) : void {
            global $eventPublisher, $scheduler;

            if ($message["action"] === "RESET_OPENING_BALANCES") {
                $timeSinceBeginningOfYear = strtotime("1.1." . date("Y", time()));

                if ($timeSinceBeginningOfYear < $message["timeSinceLastExecution"]) {
                    $eventPublisher->publishVacationResetEvent();                        
                    $scheduler->recordEventsTriggered($message["action"]);
                }
            }
        }
    }
?>