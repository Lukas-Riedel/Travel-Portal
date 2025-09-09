<?php
    namespace Core\Service\Flight;

    use Core\Common\CommonConstants;
    use Core\Service\Category\CategoryService;
    use Core\Service\Geocoding\GeocodingService;
    use Core\Service\Trip\TripService;
    use enshrined\svgSanitize\Sanitizer;
    use enshrined\svgSanitize\data\AllowedTags;

    class FlightService {

        private const UTC_TIMEZONE = "UTC";
        private const AIRPORT_LOCATION_FORMAT = "%s Airport";
        private const GET_FLIGHT_API_ENDPOINT_FORMAT = "https://api.flightradar24.com/common/v1/flight/list.json?&fetchBy=flight&page=1&limit=20&query=%s";
        private const EXPECTED_FLIGHT_STATUS = "Landed";
        private const FLIGHT_EVENT_NAME_FORMAT = "%s - %s (%s %s)";
        private const FLIGHT_EVENT_NAME_PATTERN = "{(.+) - (.+) \((.+)\)}";
        private const OLD_FLIGHT_EVENT_TEMPORARY_TABLE = "old_flight_event";

        private readonly FlightMapper $flightMapper;

        private readonly GeocodingService $geocodingService;

        private readonly \HttpClient $httpClient;

        private readonly \CalendarClient $calendarClient;

        private readonly \GoogleApiClient $googleApiClient;

        private readonly \EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, GeocodingService $geocodingService, CategoryService $categoryService,
            \HttpClient $httpClient, \CalendarClient $calendarClient,
            \GoogleApiClient $googleApiClient, \EventPublisher $eventPublisher) {
            $this->flightMapper = new FlightMapper($databaseProvider, $categoryService, $geocodingService);
            $this->geocodingService = $geocodingService;
            $this->httpClient = $httpClient;
            $this->calendarClient = $calendarClient;
            $this->googleApiClient = $googleApiClient;
            $this->eventPublisher = $eventPublisher;
        }
        public function getLoggedFlightsWithoutEvent() : array {
            return $this->flightMapper->selectLoggedFlightsWithoutEvent();
        }

        public function getLoggedFlightsForInterval(int $start, int $end, FlightSortingStrategy $flightSortingStrategy) : array {
            return $this->flightMapper->selectLoggedFlightsForInterval($start, $end, $flightSortingStrategy);
        }

        public function getAllNonLoggedFlights() : array {
            return $this->flightMapper->selectAllNonLoggedFlights();
        }

        public function getFirstNonLoggedFlight() : ?Flight {
            $allNonLoggedFlights = $this->flightMapper->selectAllNonLoggedFlights();
            return count($allNonLoggedFlights) > 0 ? $allNonLoggedFlights[0] : null;
        }

        public function getAverageFlightDelay() : int {
            return $this->flightMapper->selectAverageFlightDelay();
        }

        public function getTripIdForFlight(Flight $flight) : ?string {
            return $this->flightMapper->selectTripIdForFlight($flight);
        }

        public function getAllAirlines() : array {
            return $this->flightMapper->selectAirlines();
        }

        public function getAllAirports() : array {
            // TODO: Implement in a more efficient way.
            $allFlights = $this->getLoggedFlightsForInterval(0, PHP_INT_MAX, FlightSortingStrategy::ScheduledDepartureTimeAscending);
            $fromAirports = array_map(fn($flight) => $flight->getFrom(), $allFlights);
            $toAirports = array_map(fn($flight) => $flight->getTo(), $allFlights);
            $allAirports = array_merge($fromAirports, $toAirports);
            return array_values(array_reduce($allAirports, function ($carry, $airport) {
                if (!isset($carry[$airport->getId()])) {
                    $carry[$airport->getId()] = $airport;
                }
                return $carry;
            }, array()));
        }

        public function getAirport(string $airportId) : ?Airport {
            // TODO: Implement in a more efficient way.
            return current(array_filter($this->getAllAirports(), fn($airport) => $airport->getId() === $airportId)) ?: null;
        }

        public function getAirline(string $airlineId) : ?Airline {
            return $this->flightMapper->selectAirline($airlineId);
        }

        public function getUnassignedAirlineCodes() : array {
            return $this->flightMapper->selectUnassignedAirlineCodes();
        }

        public function getAirlineForFlight(string $flight) : ?Airline {
            return $this->flightMapper->selectAirlineByCode($this->getAirlineCodeForFlight($flight));
        }

        public function fetchAndLogFlight(string $flight, string $originAirportName, string $destinationAirportName, int $scheduledDeparture) : Flight {
            date_default_timezone_set(self::UTC_TIMEZONE);
            $apiResponse = $this->httpClient->executeRequest(\HttpMethod::GET, sprintf(self::GET_FLIGHT_API_ENDPOINT_FORMAT, $flight));

            $selectedFlight = null;
            foreach ($apiResponse["result"]["response"]["data"] as &$fetchedFlight) {
                if (($fetchedFlight["time"]["scheduled"]["departure"] - CommonConstants::ONE_HOUR_SECONDS <= $scheduledDeparture) && ($fetchedFlight["time"]["scheduled"]["departure"] + CommonConstants::ONE_HOUR_SECONDS >= $scheduledDeparture)) {
                    $selectedFlight = $fetchedFlight;
                    break;
                }
            }

            if ($selectedFlight === null) {
                throw new \RuntimeException("Cannot log the flight " . $flight . " departing at " . $scheduledDeparture . ". Is the departure time correct?");
            }
            
            if (!str_starts_with($selectedFlight["status"]["text"], self::EXPECTED_FLIGHT_STATUS)) {
                throw new \RuntimeException("Cannot log the flight " . $flight . " because its status is \"" . $selectedFlight["status"]["text"] . "\" (shall be \"" . self::EXPECTED_FLIGHT_STATUS . "\").");
            }

            return $this->logFlight($flight, $originAirportName, $selectedFlight["airport"]["origin"]["code"]["iata"], $destinationAirportName,
                $selectedFlight["airport"]["destination"]["code"]["iata"], intval($selectedFlight["time"]["scheduled"]["departure"]), intval($selectedFlight["time"]["real"]["departure"]),
                intval($selectedFlight["time"]["scheduled"]["arrival"]), intval($selectedFlight["time"]["real"]["arrival"]), $selectedFlight["aircraft"]["registration"], $selectedFlight["aircraft"]["model"]["code"]);
        }

        public function logFlight(string $flight, string $originAirportName, string $originAirportCode, string $destinationAirportName, string $destinationAirportCode,
            int $scheduledDeparture, int $actualDeparture, int $scheduledArrival, int $actualArrival, string $registration, string $aircraft) : Flight {    
            $airlineCodeId = $this->getOrCreateAirlineCodeId($this->getAirlineCodeForFlight($flight));
            $originAirportIdentifier = $this->getOrCreateAirportIdentifier($originAirportCode);
            $destinationAirportIdentifier = $this->getOrCreateAirportIdentifier($destinationAirportCode);

            $from = new Airport($originAirportIdentifier->getId(), $originAirportName, $originAirportIdentifier->getLongName(), $originAirportIdentifier->getCode(), $originAirportIdentifier->getCountry(), 
                $originAirportIdentifier->getLatitude(), $originAirportIdentifier->getLongitude(), $originAirportIdentifier->getTimezone());
            $to = new Airport($destinationAirportIdentifier->getId(), $destinationAirportName, $destinationAirportIdentifier->getLongName(), $destinationAirportIdentifier->getCode(), $destinationAirportIdentifier->getCountry(),
                $destinationAirportIdentifier->getLatitude(), $destinationAirportIdentifier->getLongitude(), $destinationAirportIdentifier->getTimezone());
            $distance = $this->geocodingService->getDistance($originAirportIdentifier->getLatitude(), $originAirportIdentifier->getLongitude(),
                $destinationAirportIdentifier->getLatitude(), $destinationAirportIdentifier->getLongitude());
                

            $this->flightMapper->deleteLoggedFlight($flight, $actualDeparture, $actualArrival);
            $result = new Flight($flight, $registration, $aircraft, null, $distance, $from, $to, $actualDeparture, $actualArrival, $actualArrival - $scheduledArrival);
            $this->flightMapper->insertFlight($result, $airlineCodeId, $scheduledDeparture, $scheduledArrival);

            $this->eventPublisher->publishFlightLoggedEvent($result);

            return $result;
        }

        public function createAirline(string $name, ?string $logo) : Airline {
            $airline = new Airline(null, $name, array(), $logo);
            $this->flightMapper->insertAirline($airline);
            return $airline;
        }

        public function createFlight(FlightType $flightType, string $flight, string $originAirportName, string $destinationAirportName, int $scheduledDeparture, int $scheduledArrival) : Flight {
            $this->googleApiClient->createCalendarEvent($flightType->getCalendar()->value,
                $this->getFlightEventName($flight, $originAirportName, $destinationAirportName), null, $scheduledDeparture, $scheduledArrival);
            
            $from = new Airport(null, $originAirportName, null, null, null, null, null, null);
            $to = new Airport(null, $destinationAirportName, null, null, null, null, null, null);
            return new Flight($flight, null, null, null, null, $from, $to, $scheduledDeparture, $scheduledArrival, null);
        }

        public function getScheduledFlightsForTrip(string $tripId) : array {
            return $this->flightMapper->selectFlightsForTrip(FlightType::Scheduled, $tripId);
        }

        public function getWatchedFlightsForTrip(string $tripId) : array {
            return $this->flightMapper->selectFlightsForTrip(FlightType::Watched, $tripId);
        }

        public function updateAirlineName(string $airlineCode, string $name) : bool {
            return $this->flightMapper->updateAirlineName($airlineCode, $name);
        }

        public function updateAirlineLogo(string $airlineCode, string $logo) : bool {
            $sanitizer = new Sanitizer();
            $allowedTags = new AllowedTags(array(
                "svg" => array("xmlns", "viewBox", "width", "height"),
                "g"   => array("transform"),
                "path"=> array("d", "fill", "stroke", "stroke-width", "transform")
            ));

            $sanitizer->setAllowedTags($allowedTags);
            $sanitizer->removeRemoteReferences(true);

            return $this->flightMapper->updateAirlineLogo($airlineCode, $sanitizer->sanitize($logo));
        }

        public function updateAirlineCodeAirline(string $airlineCode, ?string $airlineId) : bool {
            return $this->flightMapper->updateAirlineCodeAirline($this->flightMapper->selectAirlineCodeId($airlineCode), $airlineId);
        }

        public function updateAirportName(string $airportId, string $name) : bool {
            return $this->flightMapper->updateAirportName($airportId, $name);
        }

        public function removeAirline(string $airlineId) : bool {
            return $this->flightMapper->deleteAirline($airlineId) > 0;
        }

        public function refreshCalendar(array $flightTypes, TripService $tripService) : void {
            foreach ($flightTypes as &$flightType) {
                $this->doRefreshCalendar($flightType, $tripService);
            }
        }

        private function getOrCreateAirlineCodeId(string $code) : string {
            $airlineCodeId = $this->flightMapper->selectAirlineCodeId($code);
            if ($airlineCodeId !== null) {
                return $airlineCodeId;
            }

            $this->flightMapper->insertAirlineCodeId($code);

            return $this->flightMapper->selectAirlineCodeId($code);
        }
        
        private function getOrCreateAirportIdentifier(string $code) : AirportIdentifier {
            $airportIdentifier = $this->flightMapper->selectAirportIdentifier($code);
            if ($airportIdentifier !== null) {
                return $airportIdentifier;
            }
            
            $location = $this->geocodingService->getLocation(sprintf(self::AIRPORT_LOCATION_FORMAT, $code));
            $airportIdentifier = new AirportIdentifier(null, null, $code, $location->getCountry(), $location->getLatitude(),
                $location->getLongitude(), $location->getTimezone());                
            $this->flightMapper->insertAirportIdentifier($airportIdentifier);

            return $airportIdentifier;
        }
        
        private function doRefreshCalendar(FlightType $flightType, TripService $tripService) : void {   
            if ($flightType === FlightType::Scheduled) {
                $this->flightMapper->createFlightEventTemporaryTable(self::OLD_FLIGHT_EVENT_TEMPORARY_TABLE);
            }     
            $this->flightMapper->deleteAllFlightEvents($flightType);

            $flightEvents = $this->calendarClient->getEvents($flightType->getCalendar()->value);
            foreach ($flightEvents as &$flightEvent) {
                $parsedFlightEventName = $this->parseFlightEventName($flightEvent->getSummary());                
                $resolvedTripIdentifier = $tripService->getOrCreateTripIdentifierForEntity($flightEvent->getStart(), $flightEvent->getEnd());

                $from = new Airport(null, $parsedFlightEventName["from"], null, null, null, null, null, null);
                $to = new Airport(null, $parsedFlightEventName["to"], null, null, null, null, null, null);
                $flight = new Flight($parsedFlightEventName["flight"], null, null, null, null, $from, $to, $flightEvent->getStart(), $flightEvent->getEnd(), null);

                $this->flightMapper->insertFlightEvent($flightType, $flight, $flightEvent->getId(), $resolvedTripIdentifier->getId());
            }   

            if ($flightType === FlightType::Scheduled) {
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
                    $this->eventPublisher->publishFlightEventRemovedEvent($affectedTripId);
                }
            }              
        }

        private function getAirlineCodeForFlight(string $flight) : string {
            return substr($flight, 0, 2);
        }

        private function getNumberForFlight(string $flight) : string {
            return substr($flight, 2);
        }

        private function getFlightEventName(string $flight, string $originAirportName, string $destinationAirportName) : string {
            return sprintf(self::FLIGHT_EVENT_NAME_FORMAT, $originAirportName, $destinationAirportName,
                $this->getAirlineCodeForFlight($flight), $this->getNumberForFlight($flight));
        }

        private function parseFlightEventName(string $flightEventName) : mixed {
            preg_match(self::FLIGHT_EVENT_NAME_PATTERN, $flightEventName, $tokens);
            return array("from" => $tokens[1], "to" => $tokens[2], "flight" => str_replace(" ", "", $tokens[3]));
        }
    }
?>