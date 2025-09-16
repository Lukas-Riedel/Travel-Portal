<?php
    namespace Core\Service\Stay;

    use Core\Client\Calendar\Calendar;
    use Core\Service\Trip\TripService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Calendar\CalendarClient;

    class StayService {

        private const OLD_STAY_EVENT_TEMPORARY_TABLE = "old_stay_event";

        private readonly StayMapper $stayMapper;

        private readonly CalendarClient $calendarClient;

        private readonly EventPublisher $eventPublisher;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, CalendarClient $calendarClient, EventPublisher $eventPublisher) {
            $this->stayMapper = new StayMapper($databaseClient);
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
        }
        
        public function getStaysForTrip(string $tripId) : array {
            return $this->stayMapper->selectStaysForTrip($tripId);
        }
        
        public function getStaysForInterval(int $start, int $end, StaySortingStrategy $staySortingStrategy) : array {
            return $this->stayMapper->selectStaysForInterval($start, $end, $staySortingStrategy);
        }

        public function refreshCalendar(TripService $tripService) : void {
            $this->stayMapper->createStayEventTemporaryTable(self::OLD_STAY_EVENT_TEMPORARY_TABLE);
            $stayEvents = $this->calendarClient->getEvents(Calendar::Stays);

            $this->transactionManager->executeAtomically(function() use(&$tripService, &$stayEvents) {
                $this->stayMapper->deleteAllStayEvents();                
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
            });
        }
    }
?>