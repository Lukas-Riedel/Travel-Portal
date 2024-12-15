<?php 
    require_once(dirname(__FILE__) . "/GetDistanceProcessor.php");
    require_once(dirname(__FILE__) . "/../model/Flight.php");
    require_once(dirname(__FILE__) . "/../model/Airport.php");

    class GetLoggedFlightsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
        
            $result = array();

            $loggedFlightRows = $databaseProvider
                ->statementBuilder("SELECT f.*, fai.code AS from_airport_code, fai.latitude AS from_airport_latitude, fai.longitude AS from_airport_longitude, fai.country AS from_airport_country, fai.timezone AS from_airport_timezone, tai.code AS to_airport_code, tai.latitude AS to_airport_latitude, tai.longitude AS to_airport_longitude, tai.country AS to_airport_country, tai.timezone AS to_airport_timezone, l.* FROM flight_log l LEFT JOIN airport_identifier fai ON l.from_airport_id = fai.id LEFT JOIN airport_identifier tai ON l.to_airport_id = tai.id LEFT JOIN flight_event f ON l.scheduled_departure = f.start ORDER BY actual_departure DESC")
                ->getResultSet();
                
            $getDistanceProcessor = new GetDistanceProcessor();
            foreach ($loggedFlightRows as &$loggedFlightRow) {
                $distance = $getDistanceProcessor
                    ->process(array(
                        "aLatitude" => $loggedFlightRow["from_airport_latitude"], 
                        "aLongitude" => $loggedFlightRow["from_airport_longitude"],
                        "bLatitude" => $loggedFlightRow["to_airport_latitude"], 
                        "bLongitude" => $loggedFlightRow["to_airport_longitude"]));
                $from = new Airport($loggedFlightRow["from_airport_id"], $loggedFlightRow["from"], $loggedFlightRow["from_airport_code"], $loggedFlightRow["from_airport_country"], 
                    $loggedFlightRow["from_airport_latitude"], $loggedFlightRow["from_airport_longitude"], $loggedFlightRow["from_airport_timezone"]);
                $to = new Airport($loggedFlightRow["to_airport_id"], $loggedFlightRow["to"], $loggedFlightRow["to_airport_code"], $loggedFlightRow["to_airport_country"], 
                    $loggedFlightRow["to_airport_latitude"], $loggedFlightRow["to_airport_longitude"], $loggedFlightRow["to_airport_timezone"]);

                $result[] = new Flight($loggedFlightRow["flight"], $loggedFlightRow["registration"], $loggedFlightRow["aircraft"], $distance, $from, $to, $loggedFlightRow["actual_departure"], $loggedFlightRow["actual_arrival"]);
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }
    }
?>