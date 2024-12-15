<?php
    require_once(dirname(__FILE__) . "/../model/Flight.php");
    require_once(dirname(__FILE__) . "/../model/Airport.php");
    require_once(dirname(__FILE__) . "/../model/AirportIdentifier.php");
    require_once(dirname(__FILE__) . "/../processor/GetCoordsProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetHttpResponseProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetDistanceProcessor.php");

    class FlightService {
        public function getAirportIdentifier($code) : ?AirportIdentifier {
            global $databaseProvider;
            
            $airportIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM airport_identifier WHERE code = ?")
                ->withParameters($code)
                ->getFirstRow();

            if ($airportIdentifierRow === NULL) {
                return NULL;
            }
            
            return new AirportIdentifier($airportIdentifierRow["id"], $airportIdentifierRow["code"], $airportIdentifierRow["country"], 
                $airportIdentifierRow["latitude"], $airportIdentifierRow["longitude"], $airportIdentifierRow["timezone"]);
        }
        
        public function getOrCreateAirportIdentifier($code) : AirportIdentifier {
            global $databaseProvider;

            $airportIdentifier = $this->getAirportIdentifier($code);
            if ($airportIdentifier !== NULL) {
                return $airportIdentifier;
            }
            
            $location = (new GetCoordsProcessor())
                ->process(array(
                    "address" => $code . " Airport"));

            $databaseProvider
                ->statementBuilder("INSERT INTO airport_identifier (code, latitude, longitude, country, timezone) VALUES (?, ?, ?, ?, ?)")
                ->withParameters($code, $location->getLatitude(), $location->getLongitude(), $location->getCountry(), $location->getTimezone())
                ->execute();

            return $this->getAirportIdentifier($code);
        }

        public function fetchAndLogFlight($flight, $tripId, $originAirportName, $destinationAirportName, $scheduledDeparture) : Flight {
            date_default_timezone_set("UTC");
            $apiResponse = (new GetHttpResponseProcessor())
                ->process(array(
                    "method" => "GET", 
                    "url" => "https://api.flightradar24.com/common/v1/flight/list.json?&fetchBy=flight&page=1&limit=20&query=" . $flight));

            $selectedFlight = NULL;
            foreach ($apiResponse["result"]["response"]["data"] as &$flight) {
                if (($flight["time"]["scheduled"]["departure"] - 3600 <= $scheduledDeparture) && ($flight["time"]["scheduled"]["departure"] + 3600 >= $scheduledDeparture)) {
                    $selectedFlight = $flight;
                    break;
                }
            }

            if ($selectedFlight === NULL) {
                throw new RuntimeException("Cannot log the flight " . $flight . " departing at " . $scheduledDeparture . ". Is the departure time correct?");
            }
            
            if (!str_starts_with($selectedFlight["status"]["text"], "Landed")) {
                throw new RuntimeException("Cannot log the flight " . $flight . " because its status is \"" . $selectedFlight["status"]["text"] . "\" (shall be \"Landed\").");
            }

            return $this->logFlight($flight, $tripId, $originAirportName, $selectedFlight["airport"]["origin"]["code"]["iata"], $destinationAirportName,
                $selectedFlight["airport"]["destination"]["code"]["iata"], $selectedFlight["time"]["scheduled"]["departure"], $selectedFlight["time"]["real"]["departure"],
                $selectedFlight["time"]["scheduled"]["arrival"], $selectedFlight["time"]["real"]["arrival"], $selectedFlight["aircraft"]["registration"], $selectedFlight["aircraft"]["model"]["code"]);
        }

        public function logFlight($flight, $tripId, $originAirportName, $originAirportCode, $destinationAirportName, $destinationAirportCode,
            $scheduledDeparture, $actualDeparture, $scheduledArrival, $actualArrival, $registration, $aircraft) : Flight {
            global $databaseProvider, $schedulingProvider;
            
            $originAirportIdentifier = $this->getOrCreateAirportIdentifier($originAirportCode);
            $destinationAirportIdentifier = $this->getOrCreateAirportIdentifier($destinationAirportCode);

            $databaseProvider
                ->statementBuilder("DELETE FROM flight_log WHERE flight = ? AND actual_departure = ? AND actual_arrival = ?")
                ->withParameters($flight, $actualDeparture, $actualArrival)
                ->execute();

            $databaseProvider
                ->statementBuilder("INSERT INTO flight_log (flight, registration, aircraft, from_airport_id, to_airport_id, scheduled_departure, actual_departure, scheduled_arrival, actual_arrival) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->withParameters($flight, $registration, $aircraft, $originAirportIdentifier->getId(), $destinationAirportIdentifier->getId(), $scheduledDeparture, $actualDeparture, $scheduledArrival, $actualArrival)
                ->execute();

            if ($tripId !== NULL) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $tripId), NULL);
            }

            $from = new Airport($originAirportIdentifier->getId(), $originAirportName, $originAirportIdentifier->getCode(), $originAirportIdentifier->getCountry(), 
                $originAirportIdentifier->getLatitude(), $originAirportIdentifier->getLongitude(), $originAirportIdentifier->getTimezone());
            $to = new Airport($destinationAirportIdentifier->getId(), $destinationAirportName, $destinationAirportIdentifier->getCode(), $destinationAirportIdentifier->getCountry(),
                $destinationAirportIdentifier->getLatitude(), $destinationAirportIdentifier->getLongitude(), $destinationAirportIdentifier->getTimezone());

            $distance = (new GetDistanceProcessor())
                ->process(array(
                    "aLatitude" => $originAirportIdentifier->getLatitude(), 
                    "aLongitude" => $originAirportIdentifier->getLongitude(),
                    "bLatitude" => $destinationAirportIdentifier->getLatitude(), 
                    "bLongitude" => $destinationAirportIdentifier->getLongitude()));
                
            return new Flight($flight, $registration, $aircraft, $distance, $from, $to, $actualDeparture, $actualArrival);
        }

        public function createFlight($flight, $originAirportName, $destinationAirportName, $scheduledDeparture, $scheduledArrival) : Flight {
            global $googleApiClient;

            $eventName = $originAirportName . " - " . $destinationAirportName . " (" . substr($flight, 0, 2) . " " . substr($flight, 2) . ")";
            $googleApiClient->createCalendarEvent("flights", $eventName, NULL, $scheduledDeparture, $scheduledArrival);
            
            $from = new Airport(NULL, $originAirportName, NULL, NULL, NULL, NULL, NULL);
            $to = new Airport(NULL, $destinationAirportName, NULL, NULL, NULL, NULL, NULL);
            return new Flight($flight, NULL, NULL, NULL, $from, $to, $scheduledDeparture, $scheduledArrival);
        }
    }
?>