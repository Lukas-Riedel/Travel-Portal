<?php
    require_once(dirname(__FILE__) . "/GetCoordsProcessor.php");
    require_once(dirname(__FILE__) . "/../model/Flight.php");
    require_once(dirname(__FILE__) . "/../model/Airport.php");

    class GetWatchedFlightsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $getCoordsProcessor = new GetCoordsProcessor();
            
            $result = array();

            $watchedFlightRows = $databaseProvider
                ->statementBuilder("SELECT * FROM flight_watched_event WHERE start > UNIX_TIMESTAMP() ORDER BY start ASC")
                ->getResultSet();

            foreach ($watchedFlightRows as &$watchedFlightRow) {
                $fromTimezone = $getCoordsProcessor
                    ->process(array(
                        "address" => $watchedFlightRow["from"]))->getTimezone();
                $toTimezone = $getCoordsProcessor
                    ->process(array(
                        "address" => $watchedFlightRow["to"]))->getTimezone();

                $result[] = new Flight($watchedFlightRow["flight"], NULL, NULL, NULL, new Airport(NULL, $watchedFlightRow["from"], NULL, NULL, NULL, NULL, NULL),
                    new Airport(NULL, $watchedFlightRow["to"], NULL, NULL, NULL, NULL, NULL), $watchedFlightRow["start"], $watchedFlightRow["end"]);
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }

        private function getLocalTimestamp($homeTimezoneTimestamp, $placeTimezone) {   
            global $configuration;

            $timezone = new DateTimeZone($placeTimezone);
            $dateTimeHome = new DateTime("now", new DateTimeZone($configuration["homeLocation"]["timezone"]));
            $dateTimeHome->setTimestamp($homeTimezoneTimestamp);
            $timeOffset = $timezone->getOffset($dateTimeHome) - (new DateTimeZone($configuration["homeLocation"]["timezone"]))->getOffset($dateTimeHome);
            return $homeTimezoneTimestamp + $timeOffset;
        }
    }
?>