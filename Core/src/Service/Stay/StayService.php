<?php
    namespace Core\Service\Stay;

    use Core\Client\Calendar\Calendar;
    use Core\Service\Trip\TripService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Calendar\CalendarClient;
    use Core\Client\Google\GoogleClient;
    use Core\Common\CommonConstants;

    class StayService {

        private const OLD_STAY_EVENT_TEMPORARY_TABLE = "old_stay_event";

        private readonly StayMapper $stayMapper;

        private readonly CalendarClient $calendarClient;
        private readonly GoogleClient $googleClient;

        private readonly EventPublisher $eventPublisher;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, CalendarClient $calendarClient,
            GoogleClient $googleClient, EventPublisher $eventPublisher) {
            $this->stayMapper = new StayMapper($databaseClient);
            $this->calendarClient = $calendarClient;
            $this->googleClient = $googleClient;
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
                    $resolvedTripIdentifier = $tripService->getTripIdentifierForEntity($stayEvent->getStart(), $stayEvent->getEnd());
                    $stay = new Stay($stayEvent->getSummary(), $stayEvent->getLocation(), $stayEvent->getStart(), $stayEvent->getEnd());

                    $this->stayMapper->insertStayEvent($stay, $stayEvent->getId(), $resolvedTripIdentifier?->getId());

                    if (!$stayEvent->isAllDay()) {
                        $this->googleClient->updateCalendarEventAllDayDates(Calendar::Stays, $stayEvent->getId(),
                            // If the event ends at 14:00, make it ends at midnight the next day.
                            $stayEvent->getStart(), $stayEvent->getEnd() + CommonConstants::ONE_DAY_SECONDS - 1);
                    }
                }
            });
                
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