<?php
    require_once(dirname(__FILE__) . "/../model/Flight.php");
    require_once(dirname(__FILE__) . "/../model/Airport.php");
    require_once(dirname(__FILE__) . "/../model/AirportIdentifier.php");

    class FlightService {
        public function getAirportIdentifier($code) : ?AirportIdentifier {
            global $databaseProvider;
            
            $airportIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT ai.*, ci.name AS country FROM airport_identifier ai INNER JOIN category_identifier ci ON ai.country_category_id = ci.id WHERE code = ?")
                ->withParameters($code)
                ->getFirstRow();

            if ($airportIdentifierRow === NULL) {
                return NULL;
            }
            
            return new AirportIdentifier($airportIdentifierRow["id"], $airportIdentifierRow["code"], $airportIdentifierRow["country"], 
                $airportIdentifierRow["latitude"], $airportIdentifierRow["longitude"], $airportIdentifierRow["timezone"]);
        }
        
        public function getOrCreateAirportIdentifier($code) : AirportIdentifier {
            global $databaseProvider, $geocodingService, $categoryService;

            $airportIdentifier = $this->getAirportIdentifier($code);
            if ($airportIdentifier !== NULL) {
                return $airportIdentifier;
            }
            
            $location = $geocodingService->getLocation($code . " Airport");
            
            $databaseProvider
                ->statementBuilder("INSERT INTO airport_identifier (code, latitude, longitude, country_category_id, timezone) VALUES (?, ?, ?, ?, ?)")
                ->withParameters($code, $location->getLatitude(), $location->getLongitude(),
                    $categoryService->getOrCreateCountryCategoryIdentifier($location->getCountry())->getId(), $location->getTimezone())
                ->execute();

            return $this->getAirportIdentifier($code);
        }

        public function fetchAndLogFlight($flight, $tripId, $originAirportName, $destinationAirportName, $scheduledDeparture) : Flight {
            global $httpClient;

            date_default_timezone_set("UTC");
            $apiResponse = $httpClient->executeRequest(HttpMethod::GET, "https://api.flightradar24.com/common/v1/flight/list.json?&fetchBy=flight&page=1&limit=20&query=" . $flight);

            $selectedFlight = NULL;
            foreach ($apiResponse["result"]["response"]["data"] as &$fetchedFlight) {
                if (($fetchedFlight["time"]["scheduled"]["departure"] - 3600 <= $scheduledDeparture) && ($fetchedFlight["time"]["scheduled"]["departure"] + 3600 >= $scheduledDeparture)) {
                    $selectedFlight = $fetchedFlight;
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
            global $databaseProvider, $eventPublisher, $geocodingService;
            
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
                $eventPublisher->publishTripStatisticsChangedEvent($tripId);
            }

            $from = new Airport($originAirportIdentifier->getId(), $originAirportName, $originAirportIdentifier->getCode(), $originAirportIdentifier->getCountry(), 
                $originAirportIdentifier->getLatitude(), $originAirportIdentifier->getLongitude(), $originAirportIdentifier->getTimezone());
            $to = new Airport($destinationAirportIdentifier->getId(), $destinationAirportName, $destinationAirportIdentifier->getCode(), $destinationAirportIdentifier->getCountry(),
                $destinationAirportIdentifier->getLatitude(), $destinationAirportIdentifier->getLongitude(), $destinationAirportIdentifier->getTimezone());

            $distance = $geocodingService->getDistance($originAirportIdentifier->getLatitude(), $originAirportIdentifier->getLongitude(), $destinationAirportIdentifier->getLatitude(), $destinationAirportIdentifier->getLongitude());
                
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

        public function getLoggedFlights() : array {
            global $databaseProvider, $geocodingService;
        
            $loggedFlights = array();

            $loggedFlightRows = $databaseProvider
                ->statementBuilder("SELECT f.*, fai.code AS from_airport_code, fai.latitude AS from_airport_latitude, fai.longitude AS from_airport_longitude, fci.name AS from_airport_country, fai.timezone AS from_airport_timezone, tai.code AS to_airport_code, tai.latitude AS to_airport_latitude, tai.longitude AS to_airport_longitude, tci.name AS to_airport_country, tai.timezone AS to_airport_timezone, l.* FROM flight_log l LEFT JOIN airport_identifier fai ON l.from_airport_id = fai.id LEFT JOIN category_identifier fci ON fai.country_category_id = fci.id LEFT JOIN airport_identifier tai ON l.to_airport_id = tai.id LEFT JOIN category_identifier tci ON tai.country_category_id = tci.id LEFT JOIN flight_event f ON l.scheduled_departure = f.start ORDER BY actual_departure DESC")
                ->getResultSet();
                
            foreach ($loggedFlightRows as &$loggedFlightRow) {
                $distance = $geocodingService->getDistance($loggedFlightRow["from_airport_latitude"], $loggedFlightRow["from_airport_longitude"], $loggedFlightRow["to_airport_latitude"], $loggedFlightRow["to_airport_longitude"]);
                
                $from = new Airport($loggedFlightRow["from_airport_id"], $loggedFlightRow["from"], $loggedFlightRow["from_airport_code"], $loggedFlightRow["from_airport_country"], 
                    $loggedFlightRow["from_airport_latitude"], $loggedFlightRow["from_airport_longitude"], $loggedFlightRow["from_airport_timezone"]);
                $to = new Airport($loggedFlightRow["to_airport_id"], $loggedFlightRow["to"], $loggedFlightRow["to_airport_code"], $loggedFlightRow["to_airport_country"], 
                    $loggedFlightRow["to_airport_latitude"], $loggedFlightRow["to_airport_longitude"], $loggedFlightRow["to_airport_timezone"]);

                $loggedFlights[] = new Flight($loggedFlightRow["flight"], $loggedFlightRow["registration"], $loggedFlightRow["aircraft"], $distance, $from, $to, $loggedFlightRow["actual_departure"], $loggedFlightRow["actual_arrival"]);
            }

            return $loggedFlights;
        }

        public function getFlightsForTrip($tripId) : array {
            return $this->doGetFlightsForTrip(FlightType::Scheduled, $tripId);
        }

        public function getWatchedFlightsForTrip($tripId) : array {
            return $this->doGetFlightsForTrip(FlightType::Watched, $tripId);
        }

        private function doGetFlightsForTrip($flightType, $tripId) : array {            
            global $databaseProvider, $geocodingService;

            $flightRows = $databaseProvider
                ->statementBuilder("SELECT fe.flight, fe.from, fe.to, COALESCE(fl.actual_departure, fe.start) AS start, COALESCE(fl.actual_arrival, fe.end) AS end, fl.registration, fl.aircraft, fl.from_airport_id, fl.to_airport_id, fai.code AS from_airport_code, fai.latitude AS from_airport_latitude, fai.longitude AS from_airport_longitude, fci.name AS from_airport_country, fai.timezone AS from_airport_timezone, tai.code AS to_airport_code, tai.latitude AS to_airport_latitude, tai.longitude AS to_airport_longitude, tci.name AS to_airport_country, tai.timezone AS to_airport_timezone FROM " . $flightType->getTableName() . " fe LEFT JOIN flight_log fl ON fe.flight = fl.flight AND fe.start = fl.scheduled_departure LEFT JOIN airport_identifier fai ON fl.from_airport_id = fai.id LEFT JOIN category_identifier fci ON fai.country_category_id = fci.id LEFT JOIN airport_identifier tai ON fl.to_airport_id = tai.id LEFT JOIN category_identifier tci ON tai.country_category_id = tci.id WHERE fe.trip_id = ? ORDER BY start")
                ->withParameters($tripId)
                ->getResultSet();

            $result = array();
            
            foreach ($flightRows as &$flightRow) {
                $distance = NULL;
                if ($flightRow["from_airport_latitude"] != NULL && $flightRow["from_airport_longitude"] != NULL && $flightRow["to_airport_latitude"] != NULL && $flightRow["to_airport_longitude"] != NULL) {
                    $distance = $geocodingService->getDistance($flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"]);
                }
                $from = new Airport($flightRow["from_airport_id"], $flightRow["from"], $flightRow["from_airport_code"], $flightRow["from_airport_country"], 
                    $flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["from_airport_timezone"]);
                $to = new Airport($flightRow["to_airport_id"], $flightRow["to"], $flightRow["to_airport_code"], $flightRow["to_airport_country"], 
                    $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"], $flightRow["to_airport_timezone"]);

                $result[] = new Flight($flightRow["flight"], $flightRow["registration"], $flightRow["aircraft"], $distance, $from, $to, $flightRow["start"], $flightRow["end"]);
            }
    
            return $result;
        }

        public function refreshCalendar() : void {
            global $databaseProvider, $eventPublisher, $configurationService;
            
            $databaseProvider
                ->statementBuilder("DROP TEMPORARY TABLE IF EXISTS old_flight_event")
                ->execute();
            
            $databaseProvider
                ->statementBuilder("CREATE TEMPORARY TABLE old_flight_event AS SELECT * FROM flight_event")
                ->execute();

            $this->doRefreshCalendar(FlightType::Scheduled);
            $this->doRefreshCalendar(FlightType::Watched);

            // Process new and renamed flights.
            $newFlightRows = $databaseProvider
                ->statementBuilder("SELECT nf.trip_id FROM flight_event nf LEFT JOIN old_flight_event of ON of.id = nf.id WHERE of.flight IS NULL")
                ->getResultSet();
            
            foreach ($newFlightRows as &$newFlightRow) {
                $eventPublisher->publishTripStatisticsChangedEvent($newFlightRow["trip_id"]);
            }

            // Add unknown operators to the configuration.
            $unknownOperators = $databaseProvider
                ->statementBuilder("SELECT DISTINCT SUBSTR(flight, 1, 2) AS code FROM (SELECT flight FROM flight_event UNION SELECT flight FROM flight_watched_event) f")
                ->getResultSetForColumn("code");

            foreach ($unknownOperators as &$unknownOperator) {
                $configurationService->addConfigurationEntryIfNotExists("AIRLINES", array("public", "modifiable"), $unknownOperator, $unknownOperator);
            }

            // Process removed flights.
            $removedFlightRows = $databaseProvider
                ->statementBuilder("SELECT of.trip_id FROM old_flight_event of LEFT JOIN flight_event nf ON of.id = nf.id WHERE nf.id IS NULL")
                ->getResultSet();

            foreach ($removedFlightRows as &$removedFlightRow) {
                if ($removedFlightRow["trip_id"] != NULL) {
                    $eventPublisher->publishTripStatisticsChangedEvent($removedFlightRow["trip_id"]);
                }
            }
        }
        
        public function doRefreshCalendar($flightType) : void {
            global $databaseProvider, $calendarClient, $tripService;
            
            $databaseProvider
                ->statementBuilder("DELETE FROM " . $flightType->getTableName())
                ->execute();

            foreach ($calendarClient->getEvents($flightType->getCalendarName()) as &$flightEvent) {
                preg_match("{(.+) - (.+) \((.+)\)}", $flightEvent->getSummary(), $tokens);
                
                $from = $tokens[1];
                $to = $tokens[2];
                $flight = str_replace(" ", "", $tokens[3]);
                
                $resolvedTripIdentifier = $tripService->getOrCreateTripIdentifierForEntity($flightEvent->getStart(), $flightEvent->getEnd());

                $databaseProvider
                    ->statementBuilder("INSERT INTO " . $flightType->getTableName() . " (id, flight, trip_id, start, end, `from`, `to`) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->withParameters($flightEvent->getId(), $flight, $resolvedTripIdentifier->getId(), $flightEvent->getStart(), $flightEvent->getEnd(), $from, $to)
                    ->execute();
            }            
        }

        public function onCalendarChanged($message) {
            global $configuration;

            if (($message["calendar"] === "flights" || $message["calendar"] === "watchedFlights")
                && $message["watchId"] === $configuration["googleCalendarApi"]["watchId"]) {
                $this->refreshCalendar();
            }
        }

        public function onCalendarWatchRenewing($message) {
            global $calendarClient;

            if ($message["calendar"] === "flights" || $message["calendar"] === "watchedFlights") {
                $calendarClient->watchCalendar($message["calendar"], $message["watchId"]);
            }
        }

        public function onFlightArrived($message) : void {            
            $this->fetchAndLogFlight($message["flight"], $message["tripId"], $message["from"], $message["to"], $message["scheduledDeparture"]);
        }

        public function onSchedulerTriggered($message) : void {
            global $eventPublisher, $scheduler, $databaseProvider;

            if ($message["action"] === "LOG_FLIGHTS") {
                $interval = $databaseProvider
                    ->statementBuilder("SELECT IF((SELECT end FROM flight_event fe LEFT JOIN flight_log fl ON fe.flight = fl.flight AND fe.start = fl.scheduled_departure WHERE fl.actual_arrival IS NULL ORDER BY fe.end ASC LIMIT 1) < UNIX_TIMESTAMP() - ?, 14400, (SELECT ? + end + (SELECT AVG(actual_arrival - scheduled_arrival) FROM flight_log WHERE scheduled_arrival > UNIX_TIMESTAMP() - 365 * 86400 AND actual_arrival - scheduled_arrival > 0) - UNIX_TIMESTAMP() FROM flight_event fe LEFT JOIN flight_log fl ON fe.flight = fl.flight AND fe.start = fl.scheduled_departure WHERE fl.actual_arrival IS NULL ORDER BY fe.end ASC LIMIT 1)) AS scheduler_interval")
                    ->withParameters($message["timeSinceLastExecution"], $message["timeSinceLastExecution"])
                    ->getSingleColumn("scheduler_interval");

                if ($message["timeSinceLastExecution"] > $interval) {
                    $argsList = $databaseProvider
                        ->statementBuilder("SELECT f.start AS scheduledDeparture, f.flight AS flight, f.`from` AS `from`, f.`to` AS `to`, f.trip_id AS tripId FROM flight_event f LEFT JOIN flight_log lf ON f.flight = lf.flight AND f.start = lf.scheduled_departure WHERE lf.flight IS NULL AND end < UNIX_TIMESTAMP()")
                        ->getResultSet();

                    foreach ($argsList as &$args) {
                        $eventPublisher->publishFlightArrivedEvent($args["flight"], $args["tripId"], $args["from"], $args["to"], $args["scheduledDeparture"]);
                    }
                    
                    $scheduler->recordEventsTriggered($message["action"]);
                }
            }
        }
    }

    enum FlightType {
        case Scheduled;
        case Watched;

        public function getTableName() : string {
            return match ($this) {
                self::Scheduled => "flight_event",
                self::Watched => "flight_watched_event"
            };
        }

        public function getCalendarName() : string {
            return match ($this) {
                self::Scheduled => "flights",
                self::Watched => "watchedFlights"
            };
        }
    }
?>