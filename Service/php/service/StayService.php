<?php
    require_once(dirname(__FILE__) . "/StayMapper.php");
    require_once(dirname(__FILE__) . "/../model/Stay.php");

    class StayService {

        private const OLD_STAY_EVENT_TEMPORARY_TABLE = "old_stay_event";

        private readonly StayMapper $stayMapper;

        private readonly CalendarClient $calendarClient;

        private readonly GoogleApiClient $googleApiClient;

        private readonly EventPublisher $eventPublisher;

        public function __construct(DatabaseProvider $databaseProvider, CalendarClient $calendarClient,
            GoogleApiClient $googleApiClient, EventPublisher $eventPublisher) {
            $this->stayMapper = new StayMapper($databaseProvider);
            $this->calendarClient = $calendarClient;
            $this->googleApiClient = $googleApiClient;
            $this->eventPublisher = $eventPublisher;
        }
        
        public function getStaysForTrip($tripId) : array {
            return $this->stayMapper->selectStaysForTrip($tripId);
        }

        public function refreshCalendar(TripService $tripService) : void {
            $this->stayMapper->createStayEventTemporaryTable(self::OLD_STAY_EVENT_TEMPORARY_TABLE);
            $this->stayMapper->deleteAllStayEvents();
                
            $stayEvents = $this->calendarClient->getEvents(Calendar::Stays->value);
            foreach ($stayEvents as &$stayEvent) {
                $resolvedTripIdentifier = $tripService->getOrCreateTripIdentifierForEntity($stayEvent->getStart(), $stayEvent->getEnd());
                $this->stayMapper->insertStayEvent($stayEvent, $stayEvent->getId(), $resolvedTripIdentifier->getId());
            }
            
            $affectedTripIds = $this->stayMapper->selectTripIdsForCreatedStayEvents(self::OLD_STAY_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publishStayEventCreatedEvent($affectedTripId);
            }
            
            $affectedTripIds = $this->stayMapper->selectTripIdsForUpdatedStayEvents(self::OLD_STAY_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publishStayEventUpdatedEvent($affectedTripId);
            }
            
            $affectedTripIds = $this->stayMapper->selectTripIdsForDeletedStayEvents(self::OLD_STAY_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publishStayEventDeletedEvent($affectedTripId);
            }
        }

        public function onCalendarChanged(mixed $message) : void {
            // TODO: Introduce the TripService $tripService field after moving this method to a new listener class.
            global $tripService;

            if ($message["calendar"] === Calendar::Stays->value) {
                $this->refreshCalendar($tripService);
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === Calendar::Stays->value) {
                $this->calendarClient->watchCalendar(Calendar::Stays->value);
            }
        }
    }
?>