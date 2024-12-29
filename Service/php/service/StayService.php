<?php
    require_once(dirname(__FILE__) . "/../model/Stay.php");

    class StayService {
        
        public function getStaysForTrip($tripId) : array {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT * FROM stay_event WHERE trip_id = ? ORDER BY start")
                ->withParameters($tripId)
                ->getMappedResultSet(function ($stayRow) {
                    return new Stay($stayRow["name"], $stayRow["address"], $stayRow["start"], $stayRow["end"]);
                });
        }
    }
?>