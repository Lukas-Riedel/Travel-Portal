<?php
    require_once(dirname(__FILE__) . "/../model/TimeTrackingEvent.php");

    class AddTimeTrackingEventProcessor extends Processor {     
        public function process($input) {
            global $databaseProvider;
        
            $databaseProvider
                ->statementBuilder("INSERT INTO tracking (type, hours, description, timestamp) VALUES (?, ?, ?, ?)")
                ->withParameters($input["type"], doubleval($input["hours"]), $input["description"], strtotime($input["date"]) + 9 * 3600)
                ->execute();
                
            $trackingEventRow = $databaseProvider
                ->statementBuilder("SELECT * FROM tracking ORDER BY id DESC LIMIT 1")
                ->getSingleRow();

            $balance = $databaseProvider
                ->statementBuilder("SELECT ROUND(SUM(hours), 2) AS balance FROM tracking WHERE timestamp <= ? AND type = ?")
                ->withParameters($trackingEventRow["timestamp"], $trackingEventRow["type"])
                ->getSingleColumn("balance");

            // A little hack to force the trip_summary view materialization before there's a support for propagating dependencies over functions.
            $databaseProvider
                ->statementBuilder("UPDATE view_materialization SET is_materialization_delayed = 1 WHERE view_name = '_trip_summary'")
                ->execute();

            return new TimeTrackingEvent($trackingEventRow["id"], $trackingEventRow["description"], $trackingEventRow["hours"],
                $trackingEventRow["timestamp"], $trackingEventRow["type"], $balance);
        }

        public function getRequiredArguments() {
            return array("type", "hours", "description", "date");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>