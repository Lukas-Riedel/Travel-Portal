<?php
    class FitnessService {    
        public function getFitnessRecordsForTrip($tripId) {
            global $databaseProvider;

            $fitnessRecords = array();

            $rangeRows = $databaseProvider
                ->statementBuilder("SELECT DISTINCT start - (start % 86400) AS start, start - (start % 86400) + 86400 AS end FROM place_summary WHERE trip_id = ? AND start - (start % 86400) < UNIX_TIMESTAMP() ORDER BY start")
                ->withParameters($tripId)
                ->getResultSet();
            
            foreach ($rangeRows as &$rangeRow) {
                $fitnessRow = $databaseProvider
                    ->statementBuilder("SELECT SUM(steps) AS steps, SUM(minutes) AS minutes, SUM(calories) AS calories, SUM(distance) AS distance FROM fitness WHERE timestamp >= ? AND timestamp < ?")
                    ->withParameters($rangeRow["start"], $rangeRow["end"])
                    ->getSingleRow();

                $fitnessRecords[] = new Fitness(intval($fitnessRow["steps"]), intval($fitnessRow["minutes"]), intval($fitnessRow["calories"]), doubleval($fitnessRow["distance"]));
            }
    
            return $fitnessRecords;
        }

        public function updateFitnessRecord($timestamp, $steps, $minutes, $calories, $distance) : bool {
            global $databaseProvider, $schedulingProvider, $configuration;
            
            $stepLengthRow = $databaseProvider
                ->statementBuilder("SELECT AVG(distance / steps) AS average_distance_per_step, MIN(distance / steps) AS minimum_distance_per_step, MAX(distance / steps) AS maximum_distance_per_step FROM fitness")
                ->getSingleRow();

            // Distance is recorded incorrectly, scale steps by average step length.
            if ($steps != 0 && (($distance / $steps < max(0.5, $stepLengthRow["minimum_distance_per_step"] * 0.85)) || ($distance / $steps > min($stepLengthRow["maximum_distance_per_step"] > 1.15, 1.5)))) {
                $distance = $steps * $stepLengthRow["average_distance_per_step"];
            }
            
            $existingFitnessRow = $databaseProvider
                ->statementBuilder("SELECT * FROM fitness WHERE timestamp = ?")
                ->withParameters($timestamp)
                ->getSingleRow();

            if ($existingFitnessRow != NULL && ($steps < $existingFitnessRow["steps"] || $minutes < $existingFitnessRow["minutes"] || $distance < $existingFitnessRow["distance"])) {
                return FALSE;
            }

            $databaseProvider
                ->statementBuilder("DELETE FROM fitness WHERE timestamp = ?")
                ->withParameters($timestamp)
                ->execute();

            $databaseProvider
                ->statementBuilder("INSERT INTO fitness (timestamp, last_update, steps, minutes, calories, distance) VALUES (?, UNIX_TIMESTAMP(), ?, ?, ?, ?)")
                ->withParameters($timestamp, $steps, min($minutes, $configuration["fitnessRecordDuration"] / 60), $calories, $distance)
                ->execute();

            $parentTripIds = $databaseProvider
                ->statementBuilder("SELECT trip_id FROM trip_event WHERE start <= ? AND end >= ?")
                ->withParameters($timestamp, $timestamp)
                ->getResultSetForColumn("trip_id");

            foreach ($parentTripIds as &$parentTripId) {                
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $parentTripId), NULL);
            }

            return TRUE;
        }
    }
?>