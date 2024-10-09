<?php
    require_once(dirname(__FILE__) . "/GetHttpResponseProcessor.php");

    class UpdateDaylightForecastProcessor extends Processor {        
        public function process($input) {
            global $configuration, $databaseProvider;
            
            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($input["placeId"])
                ->getSingleRow();
            
            $timestamp = intval($input["start"]);
            $oneYearAgoTimestamp = $timestamp;
            while ($oneYearAgoTimestamp > (time() - 10 * 86400)) {
                $oneYearAgoTimestamp -= 86400 * 365;
            } 
    
            $startDate = date("Y-m-d", $oneYearAgoTimestamp);
            $endDate = date("Y-m-d", $oneYearAgoTimestamp + 86400);
        
            $apiResponse = (new GetHttpResponseProcessor())
                ->process(array(
                    "method" => "GET", 
                    "url" => "https://archive-api.open-meteo.com/v1/archive?latitude=" . $placeIdentifierRow["latitude"] . "&longitude=" . $placeIdentifierRow["longitude"] . "&start_date=" . $startDate . "&end_date=" . $endDate . "&daily=sunrise,sunset&timezone=" . $configuration["homeLocation"]["timezone"] . "&timeformat=unixtime"));
    
            $result = array(
                "sunrise" => $apiResponse["daily"]["sunrise"][0], 
                "sunset" => $apiResponse["daily"]["sunset"][0]);
    
            if ($result["sunrise"] !== NULL && $result["sunset"] !== NULL) {
                $databaseProvider
                    ->statementBuilder("DELETE FROM forecast_daylight WHERE place_id = ? AND timestamp = ?")
                    ->withParameters($placeIdentifierRow["id"], $timestamp)
                    ->execute();

                $databaseProvider
                    ->statementBuilder("INSERT INTO forecast_daylight (place_id, timestamp, sunrise, sunset) VALUES (?, ?, ?, ?)")
                    ->withParameters($placeIdentifierRow["id"], $timestamp, $result["sunrise"], $result["sunset"])
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
    }
?>