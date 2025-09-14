<?php
    namespace Core\Service\Trip;

    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Expense\ExpenseService;
    use Core\Service\Fitness\FitnessService;
    use Core\Service\Flight\FlightService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Note\NoteService;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Service\Statistics\StatisticsService;
    use Core\Service\Stay\StayService;
    use Core\Service\Year\YearService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;

    class TripService {

        private const OLD_TRIP_EVENT_TEMPORARY_TABLE = "old_trip_event";

        private const BEGINNING_OF_YEAR_DATE_FORMAT = "1/1/%s 12:00:00 AM";
        private const END_OF_YEAR_DATE_FORMAT = "12/31/%s 11:59:59 PM";
        private const YEAR_FORMAT = "Y";

        private readonly TripMapper $tripMapper;

        private readonly \CalendarClient $calendarClient;
        private readonly \GoogleApiClient $googleApiClient;

        private readonly ConfigurationService $configurationService;

        private readonly PlaceService $placeService;
        private readonly YearService $yearService;
        private readonly NoteService $noteService;

        private readonly EventPublisher $eventPublisher;

        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider, \CalendarClient $calendarClient, \GoogleApiClient $googleApiClient, ConfigurationService $configurationService,
            PlaceService $placeService, StayService $stayService, FlightService $flightService, ExpenseService $expenseService, FitnessService $fitnessService,
            NoteService $noteService, HighlightService $highlightService, StatisticsService $statisticsService, YearService $yearService, EventPublisher $eventPublisher) {
            $this->tripMapper = new TripMapper($databaseProvider, $calendarClient, $placeService,
                $stayService, $flightService, $expenseService, $fitnessService, $noteService,
                $highlightService, $statisticsService, $configurationService);
            $this->calendarClient = $calendarClient;
            $this->googleApiClient = $googleApiClient;
            $this->configurationService = $configurationService;
            $this->placeService = $placeService;
            $this->yearService = $yearService;
            $this->noteService = $noteService;
            $this->eventPublisher = $eventPublisher;
            $this->databaseProvider = $databaseProvider;
        }        

        public function isDayTripsTrip(Trip $trip) : bool {
            return $trip->getName() === $this->configurationService->getConfigurationEntry("trips")["dayTripsName"];
        }

        public function getRegularTrip(string $tripId) : ?Trip {
            $regularTrips = $this->doGetRegularTrips($tripId, null, null, null, TripIncludedEntity::values(), TripSortingStrategy::OldestAscending);
            return count($regularTrips) === 1 ? $regularTrips[0] : null;
        }

        public function getTripsContainingInterval(int $start, int $end) : array {
            return array_map(fn($tripId) => $this->getRegularTrip($tripId),
                $this->tripMapper->selectTripIdsContainingInterval($start, $end));
        }

        public function getOrCreateTripIdentifierForEntity(int $entityStart, int $entityEnd) : TripIdentifier {
            $regularTripIdentifier = $this->getTripIdentifierForEntity($entityStart, $entityEnd);
            if ($regularTripIdentifier !== null) {
                return $regularTripIdentifier;
            }

            return $this->getOrCreateDayTripsTripIdentifier(date(self::YEAR_FORMAT, $entityStart));
        }

        public function getRegularTrips(?int $year, ?int $start, ?int $end, array $includedEntities, TripSortingStrategy $tripSortingStrategy) : array {
            return $this->doGetRegularTrips(null, $year, $start, $end, $includedEntities, $tripSortingStrategy);
        }

        public function getCandidateTrip(string $tripId) : ?Trip {
            $candidateTrips = $this->doGetCandidateTrips($tripId,  TripIncludedEntity::values());
            return count($candidateTrips) === 1 ? $candidateTrips[0] : null;
        }

        public function getCandidateTrips(array $includedEntities) : array {
            return $this->doGetCandidateTrips(null, $includedEntities);     
        }

        public function getTripIdentifierById(string $tripId) : ?TripIdentifier {
            return $this->tripMapper->selectTripIdentifierById($tripId);
        }

        public function updateTripMainHighlight(string $tripId, string $highlightIdentifier) : bool {
            return $this->tripMapper->updateTripMainHighlight($tripId, $highlightIdentifier);
        }

        public function updateTripName(string $tripId, string $name) : bool {
            $wasUpdated = true;
            $this->databaseProvider->executeAtomically(function() use(&$wasUpdated, &$tripId, &$name) {
                $wasUpdated &= $this->tripMapper->updateTripName($tripId, $name);

                if ($wasUpdated) {
                    $wasUpdated &= $this->googleApiClient->updateCalendarEventSummary(\Calendar::Trips->value, $this->getTripEventId($tripId), $name);
                }

                if ($wasUpdated) {
                    $this->eventPublisher->publish(Event::TripUpdated($tripId));
                }
            });
            return $wasUpdated;
        }

        public function moveTrip(string $tripId, int $start) : Trip {            
            $trip = $this->getRegularTrip($tripId);
            if ($trip === null) {
                throw new \InvalidArgumentException("The trip " . $tripId . " could not be moved because it does not exist.");
            }

            $offset = $start - $trip->getStart();
            $this->placeService->movePlaces($tripId, $offset);
            $this->googleApiClient->updateCalendarEventDates(\Calendar::Trips->value, $this->getTripEventId($tripId), $start, $trip->getEnd() + $offset);

            return $trip->withOffset($offset);
        }

        public function loadTrip(string $candidateTripId, string $targetTripId) : Trip {
            $targetTrip = $this->getRegularTrip($targetTripId);
            if ($targetTrip === null) {
                throw new \InvalidArgumentException("The trip could not be loaded to the trip " . $targetTripId . " because it does not exist.");
            }

            $this->databaseProvider->executeAtomically(function() use(&$candidateTripId, &$targetTrip, &$targetTripId) {
                $this->placeService->loadPlaces($candidateTripId, $targetTrip->getStart());
                $this->noteService->updateTripNoteOwner($candidateTripId, $targetTripId);
            });

            $this->tripMapper->deleteStaleTripIdentifiers();
            
            return $targetTrip;
        }

        public function archiveTrip(string $tripId) : Trip {            
            $trip = $this->getRegularTrip($tripId);
            if ($trip === null) {
                throw new \InvalidArgumentException("The trip " . $tripId . " could not be archived because it does not exist.");
            }
            
            $archivedTripIdentifier = $this->getOrCreateTripIdentifier($trip->getName(), null);

            $this->databaseProvider->executeAtomically(function() use(&$tripId, &$trip, &$archivedTripIdentifier) {
                $this->tripMapper->insertCandidateTrip($archivedTripIdentifier->getId());
                $this->placeService->archivePlaces($tripId, $trip->getStart(), $archivedTripIdentifier);
                $this->removeTripEvent($tripId);
                $this->noteService->updateTripNoteOwner($tripId, $archivedTripIdentifier->getId());
            });

            $this->tripMapper->deleteStaleTripIdentifiers();
            
            return $this->getCandidateTrip($archivedTripIdentifier->getId());
        }

        public function removeCandidateTrip(string $tripId) : bool {
            $wasRemoved = true;
            $this->databaseProvider->executeAtomically(function() use(&$wasRemoved, &$tripId) {                
                $wasRemoved &= $this->placeService->removeCandidateEventsForCandidateTrip($tripId) 
                    && $this->tripMapper->deleteCandidateTrip($tripId);
            });

            if ($wasRemoved) {
                $this->tripMapper->deleteStaleTripIdentifiers();
            }
            return $wasRemoved;
        }

        public function refreshCalendar() : void {
            $this->tripMapper->createTripEventTemporaryTable(self::OLD_TRIP_EVENT_TEMPORARY_TABLE);
            $tripEvents = $this->calendarClient->getEvents(\Calendar::Trips->value);
            
            $this->databaseProvider->executeAtomically(function() use(&$tripEvents) {
                $this->tripMapper->deleteAllTripEvents();            
                foreach ($tripEvents as &$tripEvent) {
                    $tripIdentifier = $this->getOrCreateTripIdentifier($tripEvent->getSummary(), date(self::YEAR_FORMAT, $tripEvent->getStart()));
                    $trip = new Trip($tripIdentifier->getId(), $tripIdentifier->getName(), $tripIdentifier->getYear(), $tripIdentifier->getMainHighlight(), 
                        $tripEvent->getStart(), $tripEvent->getEnd(), array(), array(), array(), array(), array(), array(), array(), array(), array(), array());

                    $this->tripMapper->insertTripEvent($trip, $tripEvent->getId());
                }
                
                $affectedTripIds = $this->tripMapper->selectTripIdsForCreatedTripEvents(self::OLD_TRIP_EVENT_TEMPORARY_TABLE);
                foreach ($affectedTripIds as &$affectedTripId) {
                    $this->eventPublisher->publish(Event::TripEventCreated($affectedTripId));
                }
                
                $affectedTripIds = $this->tripMapper->selectTripIdsForUpdatedTripEvents(self::OLD_TRIP_EVENT_TEMPORARY_TABLE);
                foreach ($affectedTripIds as &$affectedTripId) {
                    $this->eventPublisher->publish(Event::TripUpdated($affectedTripId));
                    $this->eventPublisher->publish(Event::TripEventUpdated($affectedTripId));
                }
                
                $affectedTripIds = $this->tripMapper->selectTripIdsForDeletedTripEvents(self::OLD_TRIP_EVENT_TEMPORARY_TABLE);
                foreach ($affectedTripIds as &$affectedTripId) {
                    $this->eventPublisher->publish(Event::TripEventRemoved($affectedTripId));
                }
            });
        }

        public function removeAllDayTripsTrips() : void {
            $this->tripMapper->deleteAllDayTripsTrips();
        }

        public function updateAllDayTripsTripsDates() : void {
            $dayTripsTripName = $this->configurationService->getConfigurationEntry("trips")["dayTripsName"];
            $trips = $this->getRegularTrips(null, null, null, array(), TripSortingStrategy::OldestAscending);
            
            foreach ($trips as &$trip) {
                if ($trip->getName() === $dayTripsTripName) {
                    $places = $this->placeService->getRegularPlaces(null, null, $trip->getId(), null, null, null, null, null, null,
                        array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
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
            
            // This is effectively a part of the calendar refetch, but it must be called from here because at the time when the calendar
            // is refetched for trips, there are no trips for day trips created yet, and they would be deleted by this call.
            $this->tripMapper->deleteStaleTripIdentifiers();
        }

        private function doGetRegularTrips(?string $tripId, ?int $year, ?int $start, ?int $end, array $includedEntities, TripSortingStrategy $tripSortingStrategy) : array {
            return $this->tripMapper->selectRegularTrips($tripId, $year, $start, $end, $includedEntities, $tripSortingStrategy);
        }

        private function doGetCandidateTrips(?string $tripId, array $includedEntities) : array {
            return $this->tripMapper->selectCandidateTrips($tripId, $includedEntities);
        }
        
        private function getOrCreateTripIdentifier(string $name, ?int $year) : TripIdentifier { 
            $tripIdentifier = $this->tripMapper->selectTripIdentifier($name, $year);
            if ($tripIdentifier !== null) {
                return $tripIdentifier;
            }
            
            // Make sure the year is registered so it can be used as a foreign key.
            if ($year !== null) {
                $this->yearService->getOrCreateYearIdentifier($year);
            }

            $tripIdentifier = new TripIdentifier(null, $name, $year, null);
            $this->tripMapper->insertTripIdentifier($tripIdentifier);
            
            return $tripIdentifier;
        }

        private function getOrCreateDayTripsTripIdentifier(int $year) : TripIdentifier {
            $name = $this->configurationService->getConfigurationEntry("trips")["dayTripsName"];
            $tripIdentifier = $this->getOrCreateTripIdentifier($name, $year);
            
            if (!$this->tripMapper->selectExistsDayTripsTrip($tripIdentifier->getId())) {
                $trip = new Trip($tripIdentifier->getId(), $tripIdentifier->getName(), $tripIdentifier->getYear(), $tripIdentifier->getMainHighlight(), 
                    $this->getBeginningOfYearTimestamp($tripIdentifier->getYear()), $this->getEndOfYearTimestamp($tripIdentifier->getYear()), array(),
                    array(), array(), array(), array(), array(), array(), array(), array(), array());
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

        private function getTripIdentifierForEntity(int $entityStart, int $entityEnd) : ?TripIdentifier {
            $tripId = $this->tripMapper->selectTripIdForEntity($entityStart, $entityEnd);
            if ($tripId === null) {
                return null;
            }
            
            return $this->getTripIdentifierById($tripId);
        }

        private function getTripEventId(string $tripId) : ?string {
            return $this->tripMapper->selectTripEventId($tripId);
        }
        
        private function removeTripEvent(string $tripId) : bool {                
            return $this->googleApiClient->deleteCalendarEvent(\Calendar::Trips->value, $this->getTripEventId($tripId));
        }
    }
?>