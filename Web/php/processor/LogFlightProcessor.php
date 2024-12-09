<?php
    require_once(dirname(__FILE__) . "/GetHttpResponseProcessor.php");
    require_once(dirname(__FILE__) . "/GetDistanceProcessor.php");
    require_once(dirname(__FILE__) . "/../model/Flight.php");
    require_once(dirname(__FILE__) . "/../model/Airport.php");

    class LogFlightProcessor extends Processor {        
        public function process($input) {
            global $flightService;

            if (isset($input["fromCode"]) && isset($input["toCode"]) && isset($input["actualDeparture"]) && isset($input["scheduledArrival"])
                && isset($input["actualArrival"]) && isset($input["registration"]) && isset($input["aircraft"])) {
                return $flightService->logFlight($input["flight"], isset($input["tripId"]) ? $input["tripId"] : NULL, $input["from"], $input["fromCode"],
                    $input["to"], $input["toCode"], $input["scheduledDeparture"], $input["actualDeparture"], $input["scheduledArrival"], $input["actualArrival"],
                    $input["registration"], $input["aircraft"]);
            }
            else {
                return $flightService->fetchAndLogFlight($input["flight"], isset($input["tripId"]) ? $input["tripId"] : NULL, $input["from"],
                    $input["to"], $input["scheduledDeparture"]);
            }
        }

        public function getRequiredArguments() {
            return array("flight", "from", "to", "scheduledDeparture");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>