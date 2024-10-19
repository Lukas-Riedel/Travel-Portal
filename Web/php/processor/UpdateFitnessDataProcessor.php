<?php
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");

    class UpdateFitnessDataProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $schedulingProvider;
            
            $stepLengthRow = $databaseProvider
                ->statementBuilder("SELECT AVG(distance / steps) AS average_distance_per_step, MIN(distance / steps) AS minimum_distance_per_step, MAX(distance / steps) AS maximum_distance_per_step FROM fitness")
                ->getSingleRow();

            $steps = $this->getValue($input["start"], "derived:com.google.step_count.delta:com.google.android.gms:estimated_steps", "intVal");
            $minutes = $this->getValue($input["start"], "derived:com.google.active_minutes:com.google.android.gms:merge_active_minutes", "intVal");
            $calories = $this->getValue($input["start"], "derived:com.google.calories.expended:com.google.android.gms:merge_calories_expended", "fpVal");
            $distance = $this->getValue($input["start"], "derived:com.google.distance.delta:com.google.android.gms:merge_distance_delta", "fpVal");

            // Distance is recorded incorrectly, scale steps by average step length.
            if ($steps != 0 && (($distance / $steps < $stepLengthRow["minimum_distance_per_step"]) || ($distance / $steps > $stepLengthRow["maximum_distance_per_step"]))) {
                $distance = $steps * $stepLengthRow["average_distance_per_step"];
            }
            
            $existingFitnessRow = $databaseProvider
                ->statementBuilder("SELECT * FROM fitness WHERE timestamp = ?")
                ->withParameters($input["start"])
                ->getSingleRow();

            if ($existingFitnessRow == NULL && $steps < $existingFitnessRow["steps"] * 0.9) {
                return FALSE;
            }

            if ($existingFitnessRow != NULL && $minutes < $existingFitnessRow["minutes"] * 0.9) {
                return FALSE;
            }

            if ($existingFitnessRow != NULL && $calories < $existingFitnessRow["calories"] * 0.9) {
                return FALSE;
            }

            if ($existingFitnessRow != NULL && $distance < $existingFitnessRow["distance"] * 0.9) {
                return FALSE;
            }

            $databaseProvider
                ->statementBuilder("DELETE FROM fitness WHERE timestamp = ?")
                ->withParameters($input["start"])
                ->execute();

            $databaseProvider
                ->statementBuilder("INSERT INTO fitness (timestamp, last_update, steps, minutes, calories, distance) VALUES (?, UNIX_TIMESTAMP(), ?, ?, ?, ?)")
                ->withParameters($input["start"], $steps, $minutes, $calories, $distance)
                ->execute();

            $parentTripIds = $databaseProvider
                ->statementBuilder("SELECT trip_id FROM trip_event WHERE start <= ? AND end >= ?")
                ->withParameters($input["start"], $input["start"])
                ->getResultSetForColumn("trip_id");

            foreach ($parentTripIds as &$parentTripId) {                
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $parentTripId), NULL);
            }

            return TRUE;
        }

        public function getRequiredArguments() {
            return array("start");
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
        
        private function getValue($start, $dataSourceId, $dataColumn) {
            global $configuration;

            $payload = array(
                "aggregateBy" => array(array("dataSourceId" => $dataSourceId)),
                "bucketByTime" => array("durationMillis" => $configuration["fitnessRecordDuration"] * 1000),
                "startTimeMillis" => $start * 1000,
                "endTimeMillis" => ($start + $configuration["fitnessRecordDuration"]) * 1000);

            $apiResponse = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://www.googleapis.com/fitness/v1/users/me/dataset:aggregate",
                    "payload" => json_encode($payload)));

            if (!isset($apiResponse["bucket"])) {
                return 0;
            }
            
            $result = 0;
            foreach ($apiResponse["bucket"] as &$bucket) {
                foreach ($bucket["dataset"] as &$dataset) {
                    foreach ($dataset["point"] as &$point) {
                        foreach ($point["value"] as &$value) {
                            $result += $value[$dataColumn];
                        }
                    }
                }
            }
            return $result;
        }
    }
?>