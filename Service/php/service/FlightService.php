<?php
    require_once(dirname(__FILE__) . "/FlightMapper.php");
    require_once(dirname(__FILE__) . "/../model/Flight.php");
    require_once(dirname(__FILE__) . "/../model/Airport.php");
    require_once(dirname(__FILE__) . "/../model/AirportIdentifier.php");

    class FlightService {

        private const LOG_FLIGHTS_ACTION_NAME = "LOG_FLIGHTS";
        private const LOG_FLIGHTS_ACTION_DEFAULT_INTERVAL = 14400;
        private const UTC_TIMEZONE = "UTC";
        private const AIRPORT_LOCATION_FORMAT = "%s Airport";
        private const GET_FLIGHT_API_ENDPOINT_FORMAT = "https://api.flightradar24.com/common/v1/flight/list.json?&fetchBy=flight&page=1&limit=20&query=%s";
        private const EXPECTED_FLIGHT_STATUS = "Landed";
        private const FLIGHT_EVENT_NAME_FORMAT = "%s - %s (%s %s)";
        private const FLIGHT_EVENT_NAME_PATTERN = "{(.+) - (.+) \((.+)\)}";
        private const OLD_FLIGHT_EVENT_TEMPORARY_TABLE = "old_flight_event";

        private readonly FlightMapper $flightMapper;

        private readonly GeocodingService $geocodingService;

        private readonly HttpClient $httpClient;

        private readonly CalendarClient $calendarClient;

        private readonly GoogleApiClient $googleApiClient;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(DatabaseProvider $databaseProvider, GeocodingService $geocodingService, CategoryService $categoryService,
            HttpClient $httpClient, CalendarClient $calendarClient, GoogleApiClient $googleApiClient, EventPublisher $eventPublisher,
            Scheduler $scheduler) {
            $this->flightMapper = new FlightMapper($databaseProvider, $categoryService, $geocodingService);
            $this->geocodingService = $geocodingService;
            $this->httpClient = $httpClient;
            $this->calendarClient = $calendarClient;
            $this->googleApiClient = $googleApiClient;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function getAirportIdentifier(string $code) : ?AirportIdentifier {
            return $this->flightMapper->selectAirportIdentifier($code);
        }
        
        public function getOrCreateAirportIdentifier(string $code) : AirportIdentifier {
            $airportIdentifier = $this->flightMapper->selectAirportIdentifier($code);
            if ($airportIdentifier !== NULL) {
                return $airportIdentifier;
            }
            
            $location = $this->geocodingService->getLocation(sprintf(self::AIRPORT_LOCATION_FORMAT, $code));
            $airportIdentifier = new AirportIdentifier(NULL, $code, $location->getCountry(), $location->getLatitude(),
                $location->getLongitude(), $location->getTimezone());                
            $this->flightMapper->insertAirportIdentifier($airportIdentifier);

            return $airportIdentifier;
        }

        public function fetchAndLogFlight(string $flight, string $tripId, string $originAirportName, string $destinationAirportName, int $scheduledDeparture) : Flight {
            date_default_timezone_set(self::UTC_TIMEZONE);
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_FLIGHT_API_ENDPOINT_FORMAT, $flight));

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
            
            if (!str_starts_with($selectedFlight["status"]["text"], self::EXPECTED_FLIGHT_STATUS)) {
                throw new RuntimeException("Cannot log the flight " . $flight . " because its status is \"" . $selectedFlight["status"]["text"] . "\" (shall be \"" . self::EXPECTED_FLIGHT_STATUS . "\").");
            }

            return $this->logFlight($flight, $tripId, $originAirportName, $selectedFlight["airport"]["origin"]["code"]["iata"], $destinationAirportName,
                $selectedFlight["airport"]["destination"]["code"]["iata"], intval($selectedFlight["time"]["scheduled"]["departure"]), intval($selectedFlight["time"]["real"]["departure"]),
                intval($selectedFlight["time"]["scheduled"]["arrival"]), intval($selectedFlight["time"]["real"]["arrival"]), $selectedFlight["aircraft"]["registration"], $selectedFlight["aircraft"]["model"]["code"]);
        }

        public function logFlight(string $flight, string $tripId, string $originAirportName, string $originAirportCode, string $destinationAirportName, string $destinationAirportCode,
            int $scheduledDeparture, int $actualDeparture, int $scheduledArrival, int $actualArrival, string $registration, string $aircraft) : Flight {            
            $originAirportIdentifier = $this->getOrCreateAirportIdentifier($originAirportCode);
            $destinationAirportIdentifier = $this->getOrCreateAirportIdentifier($destinationAirportCode);

            $from = new Airport($originAirportIdentifier->getId(), $originAirportName, $originAirportIdentifier->getCode(), $originAirportIdentifier->getCountry(), 
                $originAirportIdentifier->getLatitude(), $originAirportIdentifier->getLongitude(), $originAirportIdentifier->getTimezone());
            $to = new Airport($destinationAirportIdentifier->getId(), $destinationAirportName, $destinationAirportIdentifier->getCode(), $destinationAirportIdentifier->getCountry(),
                $destinationAirportIdentifier->getLatitude(), $destinationAirportIdentifier->getLongitude(), $destinationAirportIdentifier->getTimezone());
            $distance = $this->geocodingService->getDistance($originAirportIdentifier->getLatitude(), $originAirportIdentifier->getLongitude(),
                $destinationAirportIdentifier->getLatitude(), $destinationAirportIdentifier->getLongitude());
                

            $this->flightMapper->deleteLoggedFlight($flight, $actualDeparture, $actualArrival);
            $result = new Flight($flight, $registration, $aircraft, $distance, $from, $to, $actualDeparture, $actualArrival);
            $this->flightMapper->insertFlight($result, $scheduledDeparture, $scheduledArrival);

            $this->eventPublisher->publishFlightLoggedEvent($flight, $tripId);

            return $result;
        }

        public function createFlight(string $flight, string $originAirportName, string $destinationAirportName, int $scheduledDeparture, int $scheduledArrival) : Flight {
            $this->googleApiClient->createCalendarEvent(FlightType::Scheduled->getCalendar()->value,
                $this->getFlightEventName($flight, $originAirportName, $destinationAirportName), NULL, $scheduledDeparture, $scheduledArrival);
            
            $from = new Airport(NULL, $originAirportName, NULL, NULL, NULL, NULL, NULL);
            $to = new Airport(NULL, $destinationAirportName, NULL, NULL, NULL, NULL, NULL);
            return new Flight($flight, NULL, NULL, NULL, $from, $to, $scheduledDeparture, $scheduledArrival);
        }

        public function getLoggedFlights() : array {        
            return $this->flightMapper->selectAllLoggedFlights();
        }

        public function getFlightsForTrip(string $tripId) : array {
            return $this->flightMapper->selectFlightsForTrip(FlightType::Scheduled, $tripId);
        }

        public function getWatchedFlightsForTrip(string $tripId) : array {
            return $this->flightMapper->selectFlightsForTrip(FlightType::Watched, $tripId);
        }

        public function refreshCalendar(TripService $tripService) : void {
            $this->flightMapper->createFlightEventTemporaryTable(self::OLD_FLIGHT_EVENT_TEMPORARY_TABLE);

            $this->doRefreshCalendar(FlightType::Scheduled, $tripService);
            $this->doRefreshCalendar(FlightType::Watched, $tripService);

            $affectedTripIds = $this->flightMapper->selectTripIdsForCreatedFlightEvents(self::OLD_FLIGHT_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publishFlightEventCreatedEvent($affectedTripId);
            }
            
            $affectedTripIds = $this->flightMapper->selectTripIdsForUpdatedFlightEvents(self::OLD_FLIGHT_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publishFlightEventUpdatedEvent($affectedTripId);
            }
            
            $affectedTripIds = $this->flightMapper->selectTripIdsForDeletedFlightEvents(self::OLD_FLIGHT_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publishFlightEventDeletedEvent($affectedTripId);
            }
        }
        
        private function doRefreshCalendar(FlightType $flightType, TripService $tripService) : void {        
            $this->flightMapper->deleteAllFlightEvents($flightType);

            $flightEvents = $this->calendarClient->getEvents($flightType->getCalendar()->value);
            foreach ($flightEvents as &$flightEvent) {
                $parsedFlightEventName = $this->parseFlightEventName($flightEvent->getSummary());                
                $resolvedTripIdentifier = $tripService->getOrCreateTripIdentifierForEntity($flightEvent->getStart(), $flightEvent->getEnd());

                $from = new Airport(NULL, $parsedFlightEventName["from"], NULL, NULL, NULL, NULL, NULL);
                $to = new Airport(NULL, $parsedFlightEventName["to"], NULL, NULL, NULL, NULL, NULL);
                $flight = new Flight($parsedFlightEventName["flight"], NULL, NULL, NULL, $from, $to, $flightEvent->getStart(), $flightEvent->getEnd());

                $this->flightMapper->insertFlightEvent($flightType, $flight, $flightEvent->getId(), $resolvedTripIdentifier->getId());
            }            
        }

        private function getFlightEventName(string $flight, string $originAirportName, string $destinationAirportName) : string {
            return sprintf(self::FLIGHT_EVENT_NAME_FORMAT, $originAirportName, $destinationAirportName, substr($flight, 0, 2), substr($flight, 2));
        }

        private function parseFlightEventName(string $flightEventName) : mixed {
            preg_match(self::FLIGHT_EVENT_NAME_PATTERN, $flightEventName, $tokens);
            return array(
                "from" => $tokens[1],
                "to" => $tokens[2],
                "flight" => str_replace(" ", "", $tokens[3]));
        }

        public function onCalendarChanged(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            if ($message["calendar"] === Calendar::Flights->value || $message["calendar"] === Calendar::WatchedFlights->value) {
                $this->refreshCalendar($tripService);
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === Calendar::Flights->value || $message["calendar"] === Calendar::WatchedFlights->value) {
                $this->calendarClient->watchCalendar($message["calendar"]);
            }
        }

        public function onFlightArrived(mixed $message) : void {            
            $this->fetchAndLogFlight($message["flight"], $message["tripId"], $message["from"], $message["to"], $message["scheduledDeparture"]);
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::LOG_FLIGHTS_ACTION_NAME) {
                $firstNonLoggedFlight = $this->flightMapper->selectFirstNonLoggedFlight();
                if ($firstNonLoggedFlight === NULL) {
                    return;
                }

                $loggingInterval = $firstNonLoggedFlight->getEnd() < time() - $message["timeSinceLastExecution"]
                    ? self::LOG_FLIGHTS_ACTION_DEFAULT_INTERVAL // The flight was already tried to be logged but unsuccessfully. Try again with some delay.
                    : $message["timeSinceLastExecution"] + $firstNonLoggedFlight->getEnd() + $this->flightMapper->selectAverageDelay() - time();

                if ($message["timeSinceLastExecution"] > $loggingInterval) {
                    $this->eventPublisher->publishFlightArrivedEvent($firstNonLoggedFlight->getFlight(), $this->flightMapper->selectTripIdForFlight($firstNonLoggedFlight),
                        $firstNonLoggedFlight->getFrom()->getName(), $firstNonLoggedFlight->getTo()->getName(), $firstNonLoggedFlight->getStart());
                    $this->scheduler->recordEventsTriggered(self::LOG_FLIGHTS_ACTION_NAME);
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

        public function getCalendar() : Calendar {
            return match ($this) {
                self::Scheduled => Calendar::Flights,
                self::Watched => Calendar::WatchedFlights
            };
        }
    }
?>