<?php
    namespace Core\Service\Stay;

    use Core\Service\Statistics\KeyValuePair;
    use Core\Service\Statistics\Statistics;
    use Core\Service\Statistics\StatisticsKind;
    use Core\Service\Statistics\StatisticsProvider;
    use Core\Service\Statistics\StatisticsType;
    use Core\Service\Statistics\StatisticsUnit;

    class StayStatisticsProvider implements StatisticsProvider {

        private const TOTAL_HOTEL_NIGHTS_COUNT_STATISTICS_NAME = "TOTAL_HOTEL_NIGHTS_COUNT";
        private const AVERAGE_NIGHTS_PER_HOTEL_STATISTICS_NAME = "AVERAGE_NIGHTS_PER_HOTEL";
        private const LONGEST_HOTEL_STAYS_STATISTICS_NAME = "LONGEST_HOTEL_STAYS";

        private readonly StayService $stayService;

        public function __construct(StayService $stayService) {
            $this->stayService = $stayService;
        }

        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            $stays = $this->stayService->getStaysForInterval($start, $end, StaySortingStrategy::DurationDescending);

            if ($statisticsKind === StatisticsKind::Fact) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $totalNightsCount = array_sum(array_map(fn($stay) => $stay->getNightsCount(), $stays));
                    if ($totalNightsCount > 0) {
                        $statistics[] = new Statistics(self::TOTAL_HOTEL_NIGHTS_COUNT_STATISTICS_NAME, $totalNightsCount, StatisticsUnit::Nights);
                    }
                    
                    $averageNightsPerHotelCount = $totalNightsCount / max(count($stays), 1);
                    if ($averageNightsPerHotelCount > 0) {
                        $statistics[] = new Statistics(self::AVERAGE_NIGHTS_PER_HOTEL_STATISTICS_NAME, round($averageNightsPerHotelCount), StatisticsUnit::Nights);
                    }
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $longestStays = array_map(fn($stay) => new KeyValuePair($stay->getName(), $stay->getNightsCount()), $stays);
                    if (count($longestStays) > 0) {
                        $statistics[] = new Statistics(self::LONGEST_HOTEL_STAYS_STATISTICS_NAME, $longestStays, StatisticsUnit::Nights);
                    }
                }                
            }

            return $statistics;
        }
    }