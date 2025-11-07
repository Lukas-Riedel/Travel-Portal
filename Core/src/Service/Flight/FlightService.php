<?php
    namespace Core\Service\Flight;

    use Core\Client\Cache\CacheClient;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Category\CategoryService;
    use Core\Service\Geocoding\GeocodingService;
    use Core\Service\Trip\TripService;
    use enshrined\svgSanitize\Sanitizer;
    use enshrined\svgSanitize\data\AllowedTags;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Calendar\CalendarClient;
    use Core\Client\Google\GoogleClient;
    use Core\Client\Flight\FlightClient;
    use Core\Common\CommonConstants;

    class FlightService {

        private const AIRPORT_LOCATION_FORMAT = "%s Airport";
        private const FLIGHT_EVENT_NAME_FORMAT = "%s - %s (%s %s)";
        private const FLIGHT_EVENT_NAME_PATTERN = "{(.+) - (.+) \((.+)\)}";
        private const OLD_FLIGHT_EVENT_TEMPORARY_TABLE = "old_flight_event";
        private const FLIGHT_ESTIMATED_ARRIVAL_TIME_CACHE_KEY_FORMAT = "FlightService:%s:EstimatedArrivalTime";

        private readonly FlightMapper $flightMapper;

        private readonly GeocodingService $geocodingService;

        private readonly FlightClient $flightClient;

        private readonly CalendarClient $calendarClient;

        private readonly GoogleClient $googleClient;

        private readonly CacheClient $cacheClient;

        private readonly EventPublisher $eventPublisher;
        
        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, GeocodingService $geocodingService, CategoryService $categoryService,
            FlightClient $flightClient, CalendarClient $calendarClient, GoogleClient $googleClient, CacheClient $cacheClient, EventPublisher $eventPublisher) {
            $this->flightMapper = new FlightMapper($databaseClient, $categoryService, $geocodingService);
            $this->geocodingService = $geocodingService;
            $this->flightClient = $flightClient;
            $this->calendarClient = $calendarClient;
            $this->googleClient = $googleClient;
            $this->cacheClient = $cacheClient;
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
        }

        public function getEstimatedArrivalTime(string $flight) : ?int {
            return $this->cacheClient->get(sprintf(self::FLIGHT_ESTIMATED_ARRIVAL_TIME_CACHE_KEY_FORMAT, $flight));
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
            return array_values(array_reduce($allAirports, function($carry, $airport) {
                $carry[$airport->getId()] ??= $airport;
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
            $fetchedFlight = $this->flightClient->fetchFlight($flight, $scheduledDeparture);
            if ($fetchedFlight->getActualArrival() === null) {
                $estimatedArrival = $fetchedFlight->getEstimatedArrival();
                if ($estimatedArrival !== null) {
                    $this->cacheClient->set(sprintf(self::FLIGHT_ESTIMATED_ARRIVAL_TIME_CACHE_KEY_FORMAT, $flight), $estimatedArrival,
                        $estimatedArrival - time() + CommonConstants::ONE_HOUR_SECONDS);
                }

                throw new \RuntimeException("Cannot log the flight $flight because it has not arrived yet. Estimated arrival time is at $estimatedArrival.");
            }

            return $this->logFlight($fetchedFlight->getFlight(), $originAirportName, $fetchedFlight->getFromCode(),
                $destinationAirportName, $fetchedFlight->getToCode(), $fetchedFlight->getScheduledDeparture(),
                $fetchedFlight->getActualDeparture(), $fetchedFlight->getScheduledArrival(), $fetchedFlight->getActualArrival(),
                $fetchedFlight->getRegistration(), $fetchedFlight->getAircraft());
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
            
            $result = new Flight($flight, $registration, $aircraft, null, $distance, $from, $to, $actualDeparture, $actualArrival, $actualArrival - $scheduledArrival);
            $this->transactionManager->executeAtomically(function() use(&$result, &$airlineCodeId, &$scheduledDeparture, &$scheduledArrival) {
                $this->flightMapper->deleteLoggedFlight($result->getFlight(), $result->getStart(), $result->getEnd());
                $this->flightMapper->insertFlight($result, $airlineCodeId, $scheduledDeparture, $scheduledArrival);

                $this->eventPublisher->publish(Event::FlightLogged($result->getFlight(), $result->getFrom()->getShortName(), $result->getTo()->getShortName(), $scheduledDeparture, $scheduledArrival, 
                    $result->getEnd(), $result->getTo()->getTimezone()));  
            });

            return $result;
        }

        public function createAirline(string $name, ?string $logo) : Airline {
            $airline = new Airline(null, $name, array(), $logo);
            $this->flightMapper->insertAirline($airline);
            return $airline;
        }

        public function createFlight(FlightType $flightType, string $flight, string $originAirportName, string $destinationAirportName, int $scheduledDeparture, int $scheduledArrival) : Flight {
            $this->googleClient->createCalendarEvent($flightType->getCalendar(), $this->getFlightEventName($flight, $originAirportName, $destinationAirportName), null, $scheduledDeparture, $scheduledArrival);
            
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

            $flightEvents = $this->calendarClient->getEvents($flightType->getCalendar());
            
            $this->transactionManager->executeAtomically(function() use(&$flightType, &$flightEvents, &$tripService) {
                $this->flightMapper->deleteAllFlightEvents($flightType);
                foreach ($flightEvents as &$flightEvent) {
                    $parsedFlightEventName = $this->parseFlightEventName($flightEvent->getSummary());                
                    $resolvedTripIdentifier = $tripService->getTripIdentifierForEntity($flightEvent->getStart(), $flightEvent->getEnd());

                    $from = new Airport(null, $parsedFlightEventName["from"], null, null, null, null, null, null);
                    $to = new Airport(null, $parsedFlightEventName["to"], null, null, null, null, null, null);
                    $flight = new Flight($parsedFlightEventName["flight"], null, null, null, null, $from, $to, $flightEvent->getStart(), $flightEvent->getEnd(), null);

                    $this->flightMapper->insertFlightEvent($flightType, $flight, $flightEvent->getId(), $resolvedTripIdentifier?->getId());
                }   
            });      

            if ($flightType === FlightType::Scheduled) {
                $affectedTripIds = $this->flightMapper->selectTripIdsForCreatedFlightEvents(self::OLD_FLIGHT_EVENT_TEMPORARY_TABLE);
                foreach ($affectedTripIds as &$affectedTripId) {
                    $this->eventPublisher->publish(Event::FlightEventCreated($affectedTripId));
                }
                
                $affectedTripIds = $this->flightMapper->selectTripIdsForUpdatedFlightEvents(self::OLD_FLIGHT_EVENT_TEMPORARY_TABLE);
                foreach ($affectedTripIds as &$affectedTripId) {
                    $this->eventPublisher->publish(Event::FlightEventUpdated($affectedTripId));
                }
                
                $affectedTripIds = $this->flightMapper->selectTripIdsForDeletedFlightEvents(self::OLD_FLIGHT_EVENT_TEMPORARY_TABLE);
                foreach ($affectedTripIds as &$affectedTripId) {
                    $this->eventPublisher->publish(Event::FlightEventRemoved($affectedTripId));
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