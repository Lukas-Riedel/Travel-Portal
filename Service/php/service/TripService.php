<?php
    require_once(dirname(__FILE__) . "/TripMapper.php");
    require_once(dirname(__FILE__) . "/../model/TripIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Trip.php");

    class TripService {

        private const OLD_TRIP_EVENT_TEMPORARY_TABLE = "old_trip_event";
        
        private const UPDATE_TRIP_STATISTICS_ACTION_NAME = "UPDATE_TRIP_STATISTICS";
        private const UPDATE_TRIP_STATISTICS_ACTION_INTERVAL = 604800;

        private const BEGINNING_OF_YEAR_DATE_FORMAT = "1/1/%s 12:00:00 AM";
        private const END_OF_YEAR_DATE_FORMAT = "12/31/%s 11:59:59 PM";
        private const YEAR_FORMAT = "Y";

        private readonly TripMapper $tripMapper;

        private readonly CalendarClient $calendarClient;
        private readonly GoogleApiClient $googleApiClient;

        private readonly ConfigurationService $configurationService;

        private readonly PlaceService $placeService;
        private readonly YearService $yearService;
        private readonly NoteService $noteService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(DatabaseProvider $databaseProvider, CalendarClient $calendarClient, GoogleApiClient $googleApiClient, ConfigurationService $configurationService,
            PlaceService $placeService, StayService $stayService, FlightService $flightService, ExpenseService $expenseService, FitnessService $fitnessService,
            NoteService $noteService, HighlightService $highlightService, StatisticsService $statisticsService, YearService $yearService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->tripMapper = new TripMapper($databaseProvider, $calendarClient, $placeService, $stayService, $flightService, $expenseService, $fitnessService, $noteService, $highlightService, $statisticsService);
            $this->calendarClient = $calendarClient;
            $this->googleApiClient = $googleApiClient;
            $this->configurationService = $configurationService;
            $this->placeService = $placeService;
            $this->yearService = $yearService;
            $this->noteService = $noteService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function getRegularTrip(string $tripId) : ?Trip {
            $regularTrips = $this->doGetRegularTrips($tripId, NULL, TripIncludedEntity::values());
            return count($regularTrips) === 1 ? $regularTrips[0] : NULL;
        }

        public function getTripsContainingInterval(int $start, int $end) : array {
            return array_map(fn($tripId) => $this->getRegularTrip($tripId),
                $this->tripMapper->selectTripIdsContainingInterval($start, $end));
        }

        public function getOrCreateTripIdentifierForEntity(int $entityStart, int $entityEnd) : TripIdentifier {
            $regularTripIdentifier = $this->getTripIdentifierForEntity($entityStart, $entityEnd);
            if ($regularTripIdentifier !== NULL) {
                return $regularTripIdentifier;
            }

            return $this->getOrCreateDayTripsTripIdentifier(date("Y", $entityStart));
        }

        public function getRegularTrips(?int $year, array $includedEntities) : array {
            return $this->doGetRegularTrips(NULL, $year, $includedEntities);
        }

        public function getCandidateTrip(string $tripId) : ?Trip {
            $candidateTrips = $this->doGetCandidateTrips($tripId,  TripIncludedEntity::values());
            return count($candidateTrips) === 1 ? $candidateTrips[0] : NULL;
        }

        public function getCandidateTrips(array $includedEntities) : array {
            return $this->doGetCandidateTrips(NULL, $includedEntities);     
        }

        private function doGetRegularTrips(?string $tripId, ?int $year, array $includedEntities) : array {
            return $this->tripMapper->selectRegularTrips($tripId, $year, $includedEntities);
        }

        private function doGetCandidateTrips(?string $tripId, array $includedEntities) : array {
            return $this->tripMapper->selectCandidateTrips($tripId, $includedEntities);
        }

        private function getOrCreateDayTripsTripIdentifier(int $year) : TripIdentifier {
            $name = $this->configurationService->getConfigurationForTypeAndKey("specialTripNames", "dayTrips");
            $tripIdentifier = $this->getOrCreateTripIdentifier($name, $year);
            
            if (!$this->tripMapper->selectExistsDayTripsTrip($tripIdentifier->getId())) {
                $trip = new Trip($tripIdentifier->getId(), $tripIdentifier->getName(), $tripIdentifier->getYear(), $tripIdentifier->getMainHighlight(), 
                    $this->getBeginningOfYearTimestamp($tripIdentifier->getYear()), $this->getEndOfYearTimestamp($tripIdentifier->getYear()), array(), NULL, 0, NULL,
                    NULL, NULL, NULL, array(), array(), array(), array(), array(), array(), array(), array(), array());
                $this->tripMapper->insertDayTripsTrip($trip);
            }
            
            return $tripIdentifier;
        }

        private function getBeginningOfYearTimestamp(int $year) : int {
            return strtotime(sprintf(self::BEGINNING_OF_YEAR_DATE_FORMAT, $year));
        }
        
        private function getEndOfYearTimestamp(int $year) : int {
            return strtotime(sprintf(self::END_OF_YEAR_DATE_FORMAT, $year));
        }

        public function getTripIdentifier(string $name, ?int $year) : ?TripIdentifier {
            return $this->tripMapper->selectTripIdentifier($name, $year);
        }
        
        public function getOrCreateTripIdentifier(string $name, ?int $year) : TripIdentifier { 
            $tripIdentifier = $this->getTripIdentifier($name, $year);
            if ($tripIdentifier !== NULL) {
                return $tripIdentifier;
            }
            
            // Make sure the year is registered so it can be used as a foreign key.
            if ($year !== NULL) {
                $this->yearService->getOrCreateYearIdentifier($year);
            }

            $tripIdentifier = new TripIdentifier(NULL, $name, $year, NULL);
            $this->tripMapper->insertTripIdentifier($tripIdentifier);
            
            return $tripIdentifier;
        }

        public function getTripIdentifierById(string $tripId) : ?TripIdentifier {
            return $this->tripMapper->selectTripIdentifierById($tripId);
        }

        public function updateTripMainHighlight(string $tripId, string $highlightIdentifier) : bool {
            return $this->tripMapper->updateTripMainHighlight($tripId, $highlightIdentifier);
        }

        public function updateTripName(string $tripId, string $name) : bool {
            $wasUpdated = $this->tripMapper->updateTripName($tripId, $name);

            if ($wasUpdated) {
                $wasUpdated &= $this->googleApiClient->updateCalendarEventSummary(Calendar::Trips->value, $this->getTripEventId($tripId), $name);
            }

            if ($wasUpdated) {
                $this->eventPublisher->publishTripUpdatedEvent($tripId);
            }

            return $wasUpdated;
        }

        public function moveTrip(string $tripId, int $start) : Trip {            
            $trip = $this->getRegularTrip($tripId);
            if ($trip === NULL) {
                throw new InvalidArgumentException("The trip " . $tripId . " could not be moved because it does not exist.");
            }

            $offset = $start - $trip->getStart();
            $this->placeService->movePlaces($tripId, $offset);
            $this->googleApiClient->updateCalendarEventDates(Calendar::Trips->value, $this->getTripEventId($tripId), $start, $trip->getEnd() + $offset);

            return $trip->withOffset($offset);
        }

        public function loadTrip(string $candidateTripId, string $targetTripId) : Trip {
            $targetTrip = $this->getRegularTrip($targetTripId);
            if ($targetTrip === NULL) {
                throw new InvalidArgumentException("The trip could not be loaded to the trip " . $targetTripId . " because it does not exist.");
            }

            $this->placeService->loadPlaces($candidateTripId, $targetTrip->getStart());

            $this->noteService->updateNoteTripId($candidateTripId, $targetTripId);
            
            return $targetTrip;
        }

        public function archiveTrip(string $tripId) : Trip {            
            $trip = $this->getRegularTrip($tripId);
            if ($trip === NULL) {
                throw new InvalidArgumentException("The trip " . $tripId . " could not be archived because it does not exist.");
            }
            
            $archivedTripIdentifier = $this->getOrCreateTripIdentifier($trip->getName(), NULL);
            $this->placeService->archivePlaces($tripId, $trip->getStart(), $archivedTripIdentifier);
            $this->deleteTripEvent($tripId);

            $this->noteService->updateNoteTripId($tripId, $archivedTripIdentifier->getId());
            
            return $this->getCandidateTrip($archivedTripIdentifier->getId());
        }

        private function getTripIdentifierForEntity(int $entityStart, int $entityEnd) : ?TripIdentifier {
            $tripId = $this->tripMapper->selectTripIdForEntity($entityStart, $entityEnd);
            if ($tripId === NULL) {
                return NULL;
            }
            
            return $this->getTripIdentifierById($tripId);
        }

        private function getTripEventId(string $tripId) : ?string {
            return $this->tripMapper->selectTripEventId($tripId);
        }
        
        private function deleteTripEvent(string $tripId) : bool {                
            return $this->googleApiClient->deleteCalendarEvent(Calendar::Trips->value, $this->getTripEventId($tripId));
        }

        public function refreshCalendar() : void {
            $this->tripMapper->createTripEventTemporaryTable(self::OLD_TRIP_EVENT_TEMPORARY_TABLE);
            $this->tripMapper->deleteAllTripEvents();
            
            foreach ($this->calendarClient->getEvents(Calendar::Trips->value) as &$tripEvent) {
                $tripIdentifier = $this->getOrCreateTripIdentifier($tripEvent->getSummary(), date(self::YEAR_FORMAT, $tripEvent->getStart()));
                $trip = new Trip($tripIdentifier->getId(), $tripIdentifier->getName(), $tripIdentifier->getYear(), $tripIdentifier->getMainHighlight(), 
                    $tripEvent->getStart(), $tripEvent->getEnd(), array(), NULL, 0, NULL, NULL, NULL, NULL, array(), array(), array(), array(), array(), array(), array(), array(), array());

                $this->tripMapper->insertTripEvent($trip, $tripEvent->getId());
            }
            
            $affectedTripIds = $this->tripMapper->selectTripIdsForCreatedTripEvents(self::OLD_TRIP_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publishTripEventCreatedEvent($affectedTripId);
            }
            
            $affectedTripIds = $this->tripMapper->selectTripIdsForUpdatedTripEvents(self::OLD_TRIP_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publishTripEventUpdatedEvent($affectedTripId);
            }
            
            $affectedTripIds = $this->tripMapper->selectTripIdsForDeletedTripEvents(self::OLD_TRIP_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publishTripEventDeletedEvent($affectedTripId);
            }
        }

        public function deleteAllDayTripsTrips() : void {
            $this->tripMapper->deleteAllDayTripsTrips();
        }

        public function updateAllDayTripsTripsDates() : void {
            $dayTripsTripName = $this->configurationService->getConfigurationForTypeAndKey("specialTripNames", "dayTrips");
            $trips = $this->getRegularTrips(NULL, array());
            
            foreach ($trips as &$trip) {
                if ($trip->getName() === $dayTripsTripName) {
                    $places = $this->placeService->getRegularPlaces(NULL, NULL, $trip->getId(), NULL, NULL, NULL, NULL, array());
                    $minStart = PHP_INT_MAX;
                    $maxEnd = PHP_INT_MIN;

                    foreach ($places as &$place) {
                        foreach ($place->getDates() as &$date) {
                            if ($date->getStart() < $minStart) {
                                $minStart = $date->getStart();
                            }
                            if ($date->getEnd() > $maxEnd) {
                                $maxEnd = $date->getEnd();
                            }
                        }
                    }

                    // TODO: Extend the functionality for flights and stays.

                    $this->tripMapper->updateDayTripsTripDates($trip->getId(), $minStart, $maxEnd);
                }
            }
        }

        public function onCalendarInvalidated(mixed $message) : void {
            // TODO: Replace by fields after moving to the separate listener class.
            global $stayService, $flightService;

            // All calendars must be fetched as the entity trip ownership could change when adding/modifying/removing a trip.
            if ($message["calendar"] === Calendar::Trips->value) {
                $this->deleteAllDayTripsTrips();
                $this->refreshCalendar();
                $this->placeService->refreshCalendar($this);
                $stayService->refreshCalendar($this);
                $flightService->refreshCalendar(FlightType::cases(), $this);
                $this->updateAllDayTripsTripsDates();
            }
        }
        
        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === Calendar::Trips->value) {
                $this->calendarClient->watchCalendar(Calendar::Trips->value);
            }
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Trip->name) {
                $tripIdentifier = $this->getTripIdentifierById($message["entityId"]);
                if ($tripIdentifier !== NULL && $tripIdentifier->getMainHighlight() === NULL) {
                    $this->updateTripMainHighlight($message["entityId"], $message["highlightId"]);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {            
            if ($message["action"] === self::UPDATE_TRIP_STATISTICS_ACTION_NAME
                && $message["timeSinceLastExecution"] > self::UPDATE_TRIP_STATISTICS_ACTION_INTERVAL) {
                $dayTripsTripName = $this->configurationService->getConfigurationForTypeAndKey("specialTripNames", "dayTrips");
                $trips = $this->getRegularTrips(NULL, array());
                foreach ($trips as &$trip) {
                    if ($trip->getStart() > time() && $trip->getName() !== $dayTripsTripName) {
                        $this->eventPublisher->publishTripStatisticsInvalidatedEvent($trip->getId());
                    }
                }                        
                $this->scheduler->recordEventsTriggered(self::UPDATE_TRIP_STATISTICS_ACTION_NAME);
            }
        }
    }

    enum TripIncludedEntity : string {
        case Expenses = "EXPENSES";
        case Stays = "STAYS";
        case Flights = "FLIGHTS";
        case WatchedFlights = "WATCHED_FLIGHTS";
        case Layovers = "LAYOVERS";
        case Fitness = "FITNESS";
        case Notes = "NOTES";
        case Highlights = "HIGHLIGHTS";
        case Statistics = "STATISTICS";
        case PublicHolidays = "PUBLIC_HOLIDAYS";

        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>