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
            global $databaseProvider, $eventPublisher, $configuration;
            
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
                $databaseProvider
                    ->statementBuilder("UPDATE fitness SET last_update = UNIX_TIMESTAMP() WHERE timestamp = ?")
                    ->withParameters($timestamp)
                    ->execute();
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
                $eventPublisher->publishTripStatisticsChangedEvent($parentTripId);
            }

            return TRUE;
        }

        public function onSchedulerTriggered($message) : void {
            global $eventPublisher, $scheduler, $configuration, $databaseProvider;

            if ($message["action"] === "FETCH_FITNESS" && $message["timeSinceLastExecution"] > $configuration["fitnessRecordDuration"]) {
                $argsList = $databaseProvider
                    ->statementBuilder("SELECT x.start AS start, x.start + GET_CONFIGURATION('FITNESS_RECORD_DURATION') AS end FROM (SELECT s.seq AS start FROM fitness_sequence s JOIN (SELECT * FROM trip_event WHERE trip_id NOT IN (SELECT id FROM trip_identifier WHERE name = GET_CONFIGURATION_FOR_KEY('SPECIAL_TRIP_NAMES', 'dayTrips'))) t WHERE s.seq >= t.start AND s.seq <= t.end AND s.seq <= UNIX_TIMESTAMP() UNION SELECT s.seq AS start FROM fitness_sequence s JOIN (SELECT ps.* FROM place_summary ps INNER JOIN trip_identifier ti ON ps.trip_id = ti.id WHERE ti.name = GET_CONFIGURATION_FOR_KEY('SPECIAL_TRIP_NAMES', 'dayTrips') AND YEAR(FROM_UNIXTIME(ps.start)) = ti.year) p WHERE s.seq >= p.start - (p.start % 86400) AND s.seq <= 86400 + p.end - (p.end % 86400) AND s.seq <= UNIX_TIMESTAMP()) x LEFT JOIN fitness f ON x.start = f.timestamp WHERE f.timestamp IS NULL OR f.timestamp + (7 * 86400) > f.last_update")
                    ->getResultSet();

                foreach ($argsList as &$args) {
                    $eventPublisher->publishFitnessActivityDetectedEvent($args["start"], $args["end"]);
                }
                        
                $scheduler->recordEventsTriggered($message["action"]);
            }
        }
    }
?>