<?php
    namespace Core\Service\Trip;

    use Core\Client\Calendar\Calendar;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Expense\ExpenseService;
    use Core\Service\Fitness\FitnessService;
    use Core\Service\Flight\FlightService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Note\NoteService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Statistics\StatisticsService;
    use Core\Service\Stay\StayService;
    use Core\Service\Year\YearService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Calendar\CalendarClient;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Client\Google\GoogleClient;
    use Core\Service\Index\IndexService;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceSortingStrategy;

    class TripService {

        private const OLD_TRIP_EVENT_TEMPORARY_TABLE = "old_trip_event";

        private const YEAR_FORMAT = "Y";

        private readonly TripMapper $tripMapper;
        private readonly CalendarClient $calendarClient;
        private readonly GoogleClient $googleClient;
        private readonly GenerativeContentClient $cachingGenerativeContentClient;
        private readonly ConfigurationService $configurationService;
        private readonly PlaceService $placeService;
        private readonly YearService $yearService;
        private readonly NoteService $noteService;
        private readonly IndexService $indexService;
        private readonly HighlightService $highlightService;
        private readonly EventPublisher $eventPublisher;
        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, CalendarClient $calendarClient, GoogleClient $googleClient, GenerativeContentClient $cachingGenerativeContentClient,
            ConfigurationService $configurationService, PlaceService $placeService, StayService $stayService, FlightService $flightService, ExpenseService $expenseService,
            FitnessService $fitnessService, NoteService $noteService, HighlightService $highlightService, StatisticsService $statisticsService, YearService $yearService,
            IndexService $indexService, EventPublisher $eventPublisher) {
            $this->tripMapper = new TripMapper($databaseClient, $calendarClient, $placeService,
                $stayService, $flightService, $expenseService, $fitnessService, $noteService,
                $highlightService, $statisticsService);
            $this->calendarClient = $calendarClient;
            $this->googleClient = $googleClient;
            $this->cachingGenerativeContentClient = $cachingGenerativeContentClient;
            $this->configurationService = $configurationService;
            $this->placeService = $placeService;
            $this->yearService = $yearService;
            $this->noteService = $noteService;
            $this->indexService = $indexService;
            $this->highlightService = $highlightService;
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
        }

        public function refreshTripHighlights(string $tripId, int $count, bool $onlyTripPlaceHighlights) : void {
            $trip = $this->getRegularTrip($tripId);
            if ($trip === null) {
                return;
            }

            $places = $this->placeService->getRegularPlaces(null, null, $tripId, null, null, null, null, null,
                null, null, null, array(PlaceIncludedEntity::Highlights->value), PlaceSortingStrategy::ScoreDescending);

            $prompt = $this->configurationService->getConfigurationEntry("generativeContentPrompts")["tripHighlightsSelecting"];
            $query = $this->cachingGenerativeContentClient->getResponse($prompt, array("places" => implode(", ", array_map(fn($place) => $place->getName(), $places))));

            $tripPlaceHighlightPhotoIds = array_map(fn($highlight) => $highlight->getPhoto()->getId(), array_merge(...array_map(fn($place) => $place->getHighlights(), $places)));
            $selectedPhotoIds = $this->indexService->getSelectedPhotoIdsForTrip($tripId, $query, $count, $trip->getMainHighlight()?->getPhoto()?->getId(), $tripPlaceHighlightPhotoIds);

            foreach ($trip->getHighlights() as &$highlight) {
                if (!in_array($highlight->getPhoto()->getId(), $selectedPhotoIds)) {
                    $this->highlightService->removeTripHighlight($tripId, $highlight->getId());
                }
            }

            $existingHighlightPhotoIds = array_map(fn($highlight) => $highlight->getPhoto()->getId(), $trip->getHighlights());
            foreach ($selectedPhotoIds as &$photoId) {
                if (!in_array($photoId, $existingHighlightPhotoIds) && (!$onlyTripPlaceHighlights || in_array($photoId, $tripPlaceHighlightPhotoIds))) {
                    $this->highlightService->createTripHighlight($tripId, $photoId);
                }
            }
        }

        public function getRegularTrip(string $tripId) : ?Trip {
            $regularTrips = $this->doGetRegularTrips($tripId, null, null, null, TripIncludedEntity::values(), TripSortingStrategy::OldestAscending);
            return count($regularTrips) === 1 ? $regularTrips[0] : null;
        }

        public function getTripsContainingInterval(int $start, int $end) : array {
            return array_map(fn($tripId) => $this->getRegularTrip($tripId),
                $this->tripMapper->selectTripIdsContainingInterval($start, $end));
        }

        public function getTripIdentifierForEntity(int $entityStart, int $entityEnd) : ?TripIdentifier {
            $tripId = $this->tripMapper->selectTripIdForEntity($entityStart, $entityEnd);
            if ($tripId === null) {
                return null;
            }
            
            return $this->getTripIdentifierById($tripId);
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
            $this->transactionManager->executeAtomically(function() use(&$wasUpdated, &$tripId, &$name) {
                $wasUpdated &= $this->tripMapper->updateTripName($tripId, $name);

                if ($wasUpdated) {
                    $wasUpdated &= $this->googleClient->updateCalendarEventName(Calendar::Trips, $this->getTripEventId($tripId), $name);
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
            $this->tripMapper->updateTripYear($tripId, date(self::YEAR_FORMAT, $start));
            $this->googleClient->updateCalendarEventStartEnd(Calendar::Trips, $this->getTripEventId($tripId), $start, $trip->getEnd() + $offset, null, null);

            return $trip->withOffset($offset);
        }

        public function loadTrip(string $candidateTripId, string $targetTripId) : Trip {
            $targetTrip = $this->getRegularTrip($targetTripId);
            if ($targetTrip === null) {
                throw new \InvalidArgumentException("The trip could not be loaded to the trip " . $targetTripId . " because it does not exist.");
            }

            $this->transactionManager->executeAtomically(function() use(&$candidateTripId, &$targetTrip, &$targetTripId) {
                $this->placeService->loadPlaces($candidateTripId, $targetTrip->getStart());
                $this->noteService->updateTripNoteOwner($candidateTripId, $targetTripId);
            });

            $this->tripMapper->deleteCandidateTrip($candidateTripId);
            $this->tripMapper->deleteStaleTripIdentifiers();
            
            return $targetTrip;
        }

        public function archiveTrip(string $tripId) : Trip {            
            $trip = $this->getRegularTrip($tripId);
            if ($trip === null) {
                throw new \InvalidArgumentException("The trip " . $tripId . " could not be archived because it does not exist.");
            }
            
            $archivedTripIdentifier = $this->getOrCreateTripIdentifier($trip->getName(), null);

            $this->transactionManager->executeAtomically(function() use(&$tripId, &$trip, &$archivedTripIdentifier) {
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
            $this->transactionManager->executeAtomically(function() use(&$wasRemoved, &$tripId) {
                $this->placeService->removeCandidateEventsForCandidateTrip($tripId);             
                $wasRemoved &= $this->tripMapper->deleteCandidateTrip($tripId);
            });

            if ($wasRemoved) {
                $this->tripMapper->deleteStaleTripIdentifiers();

            }
            return $wasRemoved;
        }

        public function refreshCalendar() : void {
            $this->tripMapper->createTripEventTemporaryTable(self::OLD_TRIP_EVENT_TEMPORARY_TABLE);
            $tripEvents = $this->calendarClient->getEvents(Calendar::Trips);
            
            $this->transactionManager->executeAtomically(function() use(&$tripEvents) {
                $this->tripMapper->deleteAllTripEvents();            
                foreach ($tripEvents as &$tripEvent) {
                    $tripIdentifier = $this->getOrCreateTripIdentifier($tripEvent->getSummary(), date(self::YEAR_FORMAT, $tripEvent->getStart()));
                    $trip = new Trip($tripIdentifier->getId(), $tripIdentifier->getName(), $tripIdentifier->getYear(), $tripIdentifier->getMainHighlight(), 
                        $tripEvent->getStart(), $tripEvent->getEnd(), array(), array(), array(), array(), array(), array(), array(), array(), array(), array());

                    $this->tripMapper->insertTripEvent($trip, $tripEvent->getId());
                    
                    $homeTimezone = $this->configurationService->getConfigurationEntry("homeLocation")["timezone"];
                    if ($tripEvent->shouldBeNormalized($homeTimezone, $homeTimezone)) {
                        $this->googleClient->updateCalendarEventStartEnd(Calendar::Trips, $tripEvent->getId(), null, null, $homeTimezone, $homeTimezone);                        
                    }
                }
            });
            
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

        private function getTripEventId(string $tripId) : ?string {
            return $this->tripMapper->selectTripEventId($tripId);
        }
        
        private function removeTripEvent(string $tripId) : bool {                
            return $this->googleClient->deleteCalendarEvent(Calendar::Trips, $this->getTripEventId($tripId));
        }
    }
?>