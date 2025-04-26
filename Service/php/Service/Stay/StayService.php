<?php
    namespace Service\Service\Stay;
    
    use Service\Service\Trip\TripService;
    use Service\Service\Statistics\Statistics;
    use Service\Service\Statistics\StatisticsKind;
    use Service\Service\Statistics\StatisticsProvider;
    use Service\Service\Statistics\StatisticsType;
    use Service\Service\Statistics\StatisticsUnit;

    class StayService implements StatisticsProvider {

        private const OLD_STAY_EVENT_TEMPORARY_TABLE = "old_stay_event";

        private const TOTAL_HOTEL_NIGHTS_COUNT_STATISTICS_NAME = "TOTAL_HOTEL_NIGHTS_COUNT";
        private const AVERAGE_NIGHTS_PER_HOTEL_STATISTICS_NAME = "AVERAGE_NIGHTS_PER_HOTEL";
        private const LONGEST_HOTEL_STAYS_STATISTICS_NAME = "LONGEST_HOTEL_STAYS";

        private readonly StayMapper $stayMapper;

        private readonly \CalendarClient $calendarClient;

        private readonly \EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, \CalendarClient $calendarClient, \EventPublisher $eventPublisher) {
            $this->stayMapper = new StayMapper($databaseProvider);
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
        }

        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            if ($statisticsKind === StatisticsKind::Fact) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $totalNightsCount = $this->stayMapper->selectTotalNightsCount($start, $end);
                    if ($totalNightsCount > 0) {
                        $statistics[] = new Statistics(self::TOTAL_HOTEL_NIGHTS_COUNT_STATISTICS_NAME, $totalNightsCount, StatisticsUnit::Nights);
                    }
                    
                    $averageNightsPerHotelCount = $this->stayMapper->selectAverageNightsCountPerHotel($start, $end);
                    if ($averageNightsPerHotelCount > 0) {
                        $statistics[] = new Statistics(self::AVERAGE_NIGHTS_PER_HOTEL_STATISTICS_NAME, $averageNightsPerHotelCount, StatisticsUnit::Nights);
                    }
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $longestStays = $this->stayMapper->selectLongestStays($start, $end);
                    if (count($longestStays) > 0) {
                        $statistics[] = new Statistics(self::LONGEST_HOTEL_STAYS_STATISTICS_NAME, $longestStays, StatisticsUnit::Nights);
                    }
                }                
            }

            return $statistics;
        }
        
        public function getStaysForTrip($tripId) : array {
            return $this->stayMapper->selectStaysForTrip($tripId);
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
    }
?>