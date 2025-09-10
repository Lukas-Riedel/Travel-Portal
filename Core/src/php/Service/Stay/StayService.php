<?php
    namespace Core\Service\Stay;

    use Core\Service\Trip\TripService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;

    class StayService {

        private const OLD_STAY_EVENT_TEMPORARY_TABLE = "old_stay_event";

        private readonly StayMapper $stayMapper;

        private readonly \CalendarClient $calendarClient;

        private readonly EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, \CalendarClient $calendarClient, EventPublisher $eventPublisher) {
            $this->stayMapper = new StayMapper($databaseProvider);
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
        }
        
        public function getStaysForTrip(string $tripId) : array {
            return $this->stayMapper->selectStaysForTrip($tripId);
        }
        
        public function getStaysForInterval(int $start, int $end, StaySortingStrategy $staySortingStrategy) : array {
            return $this->stayMapper->selectStaysForInterval($start, $end, $staySortingStrategy);
        }

        public function refreshCalendar(TripService $tripService) : void {
            $this->stayMapper->createStayEventTemporaryTable(self::OLD_STAY_EVENT_TEMPORARY_TABLE);
            $this->stayMapper->deleteAllStayEvents();
                
            $stayEvents = $this->calendarClient->getEvents(\Calendar::Stays->value);
            foreach ($stayEvents as &$stayEvent) {
                $resolvedTripIdentifier = $tripService->getOrCreateTripIdentifierForEntity($stayEvent->getStart(), $stayEvent->getEnd());
                $stay = new Stay($stayEvent->getSummary(), $stayEvent->getLocation(), $stayEvent->getStart(), $stayEvent->getEnd());
                $this->stayMapper->insertStayEvent($stay, $stayEvent->getId(), $resolvedTripIdentifier->getId());
            }
            
            $affectedTripIds = $this->stayMapper->selectTripIdsForCreatedStayEvents(self::OLD_STAY_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publish(Event::StayEventCreated($affectedTripId));
            }
            
            $affectedTripIds = $this->stayMapper->selectTripIdsForUpdatedStayEvents(self::OLD_STAY_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publish(Event::StayEventUpdated($affectedTripId));
            }
            
            $affectedTripIds = $this->stayMapper->selectTripIdsForDeletedStayEvents(self::OLD_STAY_EVENT_TEMPORARY_TABLE);
            foreach ($affectedTripIds as &$affectedTripId) {
                $this->eventPublisher->publish(Event::StayEventRemoved($affectedTripId));
            }
        }
    }
?>