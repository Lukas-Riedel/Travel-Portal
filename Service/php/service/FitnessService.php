<?php
    class FitnessService {

        public function updateFitnessRecord($timestamp, $steps, $minutes, $calories, $distance) : bool {
            global $databaseProvider, $schedulingProvider;
            
            $stepLengthRow = $databaseProvider
                ->statementBuilder("SELECT AVG(distance / steps) AS average_distance_per_step, MIN(distance / steps) AS minimum_distance_per_step, MAX(distance / steps) AS maximum_distance_per_step FROM fitness")
                ->getSingleRow();

            // Distance is recorded incorrectly, scale steps by average step length.
            if ($steps != 0 && (($distance / $steps < $stepLengthRow["minimum_distance_per_step"]) || ($distance / $steps > $stepLengthRow["maximum_distance_per_step"]))) {
                $distance = $steps * $stepLengthRow["average_distance_per_step"];
            }
            
            $existingFitnessRow = $databaseProvider
                ->statementBuilder("SELECT * FROM fitness WHERE timestamp = ?")
                ->withParameters($timestamp)
                ->getSingleRow();

            if ($existingFitnessRow != NULL && ($steps < $existingFitnessRow["steps"] || $minutes < $existingFitnessRow["minutes"]
                || $calories < $existingFitnessRow["calories"] || $distance < $existingFitnessRow["distance"])) {
                return FALSE;
            }

            $databaseProvider
                ->statementBuilder("DELETE FROM fitness WHERE timestamp = ?")
                ->withParameters($timestamp)
                ->execute();

            $databaseProvider
                ->statementBuilder("INSERT INTO fitness (timestamp, last_update, steps, minutes, calories, distance) VALUES (?, UNIX_TIMESTAMP(), ?, ?, ?, ?)")
                ->withParameters($timestamp, $steps, $minutes, $calories, $distance)
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