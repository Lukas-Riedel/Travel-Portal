<?php
    require_once(dirname(__FILE__) . "/GetAirportIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetHttpResponseProcessor.php");
    require_once(dirname(__FILE__) . "/GetDistanceProcessor.php");
    require_once(dirname(__FILE__) . "/../model/Flight.php");
    require_once(dirname(__FILE__) . "/../model/Airport.php");

    class LogFlightProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $schedulingProvider;

            $fromCode = $toCode = $scheduledDeparture = $actualDeparture = $scheduledArrival = $actualArrival = $registration = $aircraft = NULL;
            if (isset($input["fromCode"]) && isset($input["toCode"]) && isset($input["actualDeparture"]) && isset($input["scheduledArrival"])
                && isset($input["actualArrival"]) && isset($input["registration"]) && isset($input["aircraft"])) {
                    $fromCode = $input["fromCode"];
                    $toCode = $input["toCode"];
                    $scheduledDeparture = $input["scheduledDeparture"];
                    $actualDeparture = $input["actualDeparture"];
                    $scheduledArrival = $input["scheduledArrival"];
                    $actualArrival = $input["actualArrival"];
                    $registration = $input["registration"];
                    $aircraft = $input["aircraft"];
            }
            else {
                date_default_timezone_set("UTC");
                $apiResponse = (new GetHttpResponseProcessor())
                    ->process(array(
                        "method" => "GET", 
                        "url" => "https://api.flightradar24.com/common/v1/flight/list.json?&fetchBy=flight&page=1&limit=20&query=" . $input["flight"]));
    
                $selectedFlight = NULL;
                foreach ($apiResponse["result"]["response"]["data"] as &$flight) {
                    if (($flight["time"]["scheduled"]["departure"] - 3600 <= $input["scheduledDeparture"]) && ($flight["time"]["scheduled"]["departure"] + 3600 >= $input["scheduledDeparture"])) {
                        $selectedFlight = $flight;
                        break;
                    }
                }
    
                if ($selectedFlight == NULL) {
                    throw new RuntimeException("Cannot log the flight " . $input["flight"] . " departing at " . $input["scheduledDeparture"] . ". Is the departure time correct?");
                }
                
                if (!str_starts_with($selectedFlight["status"]["text"], "Landed")) {
                    throw new RuntimeException("Cannot log the flight " . $input["flight"] . " because its status is \"" . $selectedFlight["status"]["text"] . "\" (shall be \"Landed\").");
                }
    
                $fromCode = $selectedFlight["airport"]["origin"]["code"]["iata"];
                $toCode = $selectedFlight["airport"]["destination"]["code"]["iata"];
                $scheduledDeparture = $selectedFlight["time"]["scheduled"]["departure"];
                $actualDeparture = $selectedFlight["time"]["real"]["departure"];
                $scheduledArrival = $selectedFlight["time"]["scheduled"]["arrival"];
                $actualArrival = $selectedFlight["time"]["real"]["arrival"];
                $registration = $selectedFlight["aircraft"]["registration"];
                $aircraft = $selectedFlight["aircraft"]["model"]["code"];
            }

            $getAirportIdentifierProcessor = new GetAirportIdentifierProcessor();
            $originAirportIdentifier = $getAirportIdentifierProcessor
                ->process(array(
                    "code" => $fromCode));
            $destinationAirportIdentifier = $getAirportIdentifierProcessor
                ->process(array(
                    "code" => $toCode));

            $databaseProvider
                ->statementBuilder("DELETE FROM flight_log WHERE flight = ? AND actual_departure = ? AND actual_arrival = ?")
                ->withParameters($input["flight"], $actualDeparture, $actualArrival)
                ->execute();

            $databaseProvider
                ->statementBuilder("INSERT INTO flight_log (flight, registration, aircraft, from_airport_id, to_airport_id, scheduled_departure, actual_departure, scheduled_arrival, actual_arrival) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->withParameters($input["flight"], $registration, $aircraft, $originAirportIdentifier->getId(), $destinationAirportIdentifier->getId(), $scheduledDeparture, $actualDeparture, $scheduledArrival, $actualArrival)
                ->execute();

            if (isset($input["tripId"])) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $input["tripId"]), NULL);
            }

            $from = new Airport($originAirportIdentifier->getId(), $input["from"], $originAirportIdentifier->getCode(), $originAirportIdentifier->getCountry(), 
                $originAirportIdentifier->getLatitude(), $originAirportIdentifier->getLongitude(), $originAirportIdentifier->getTimezone());
            $to = new Airport($destinationAirportIdentifier->getId(), $input["to"], $destinationAirportIdentifier->getCode(), $destinationAirportIdentifier->getCountry(),
                $destinationAirportIdentifier->getLatitude(), $destinationAirportIdentifier->getLongitude(), $destinationAirportIdentifier->getTimezone());

            $distance = (new GetDistanceProcessor())
                ->process(array(
                    "aLatitude" => $originAirportIdentifier->getLatitude(), 
                    "aLongitude" => $originAirportIdentifier->getLongitude(),
                    "bLatitude" => $destinationAirportIdentifier->getLatitude(), 
                    "bLongitude" => $destinationAirportIdentifier->getLongitude()));
                
            return new Flight($input["flight"], $registration, $aircraft, $distance, $from, $to, $actualDeparture, $actualArrival);
        }

        public function getRequiredArguments() {
            return array("flight", "from", "to", "scheduledDeparture");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>