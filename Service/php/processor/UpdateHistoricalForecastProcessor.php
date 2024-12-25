<?php
    class UpdateHistoricalForecastProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $configuration, $httpClient;
            
            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($input["placeId"])
                ->getSingleRow();
            
            $timestamp = intval($input["start"]);
            $oneYearAgoTimestamp = $timestamp;
            while ($oneYearAgoTimestamp > (time() - 10 * 86400)) {
                $oneYearAgoTimestamp -= 86400 * 365;
            } 
    
            $startDate = date("Y-m-d", $oneYearAgoTimestamp - 3 * 86400);
            $endDate = date("Y-m-d", $oneYearAgoTimestamp + 3 * 86400);
        
            $apiResponse = $httpClient->executeRequest("GET", "https://archive-api.open-meteo.com/v1/archive?latitude=" . $placeIdentifierRow["latitude"] . "&longitude=" . $placeIdentifierRow["longitude"] . "&start_date=" . $startDate . "&end_date=" . $endDate . "&daily=temperature_2m_max,precipitation_sum,windspeed_10m_max&timezone=" . $configuration["homeLocation"]["timezone"] . "&windspeed_unit=ms&timeformat=unixtime");
            
            $result = array(
                "temperature" => $this->getAverage($apiResponse["daily"]["temperature_2m_max"]),
                "wind" => $this->getAverage($apiResponse["daily"]["windspeed_10m_max"]),
                "precipitation" => $this->getAverage($apiResponse["daily"]["precipitation_sum"]) / 24);
    
            if ($result["temperature"] !== NULL && $result["wind"] !== NULL && $result["precipitation"] !== NULL) {    
                $databaseProvider
                    ->statementBuilder("DELETE FROM forecast_historical WHERE place_id = ? AND timestamp = ?")
                    ->withParameters($placeIdentifierRow["id"], $timestamp)
                    ->execute();

                $databaseProvider
                    ->statementBuilder("INSERT INTO forecast_historical (place_id, timestamp, temperature, wind, precipitation) VALUES (?, ?, ?, ?, ?)")
                    ->withParameters($placeIdentifierRow["id"], $timestamp, $result["temperature"], $result["wind"], $result["precipitation"])
                    ->execute();
            }

            return TRUE;
        }

        public function getRequiredArguments() {
            return array("placeId", "start");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    
        private function getAverage($values) {
            $sum = 0;
            $count = 0;
            foreach ($values as &$value) {
                if ($value !== NULL) {
                    $sum += $value;
                    $count += 1;
                }
            }
            if ($count == 0) {
                return NULL;
            }
            return $sum / $count;
        }
    }
?>