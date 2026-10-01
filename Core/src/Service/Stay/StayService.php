<?php
    namespace Core\Service\Stay;

    use Core\Client\Calendar\CalendarClient;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Google\GoogleClient;
    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Trip\TripService;

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
            return $this->stayMapper->selectStaysForTrip(StayType::Scheduled, $tripId);
        }

        public function getWatchedStaysForTrip(string $tripId) : array {
            return $this->stayMapper->selectStaysForTrip(StayType::Watched, $tripId);
        }
        
        public function getStaysForInterval(int $start, int $end, StaySortingStrategy $staySortingStrategy) : array {
            return $this->stayMapper->selectStaysForInterval($start, $end, $staySortingStrategy);
        }

        public function refreshCalendar(array $stayTypes, TripService $tripService) : void {
            foreach ($stayTypes as &$stayType) {
                $this->doRefreshCalendar($stayType, $tripService);
            }
        }

        private function doRefreshCalendar(StayType $stayType, TripService $tripService) : void {
            if ($stayType === StayType::Scheduled) {
                $this->stayMapper->createStayEventTemporaryTable(self::OLD_STAY_EVENT_TEMPORARY_TABLE);
            }

            $stayEvents = $this->calendarClient->getEvents($stayType->getCalendar());

            $this->transactionManager->executeAtomically(function() use(&$stayType, &$stayEvents, &$tripService) {
                $this->stayMapper->deleteAllStayEvents($stayType);
                foreach ($stayEvents as &$stayEvent) {
                    $resolvedTripIdentifier = $tripService->getTripIdentifierForEntity($stayEvent->getStart(), $stayEvent->getEnd());
                    $stay = new Stay($stayEvent->getSummary(), $stayEvent->getLocation(), $stayEvent->getStart(), $stayEvent->getEnd());

                    $this->stayMapper->insertStayEvent($stayType, $stay, $stayEvent->getId(), $resolvedTripIdentifier?->getId());

                    if (!$stayEvent->isAllDay()) {
                        $this->googleClient->updateCalendarEventAllDayDates($stayType->getCalendar(), $stayEvent->getId(),
                            // If the event ends at 14:00, make it ends at midnight the next day.
                            $stayEvent->getStart(), $stayEvent->getEnd() + CommonConstants::ONE_DAY_SECONDS - 1);
                    }
                }
            });

            if ($stayType === StayType::Scheduled) {
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
    }
?>
