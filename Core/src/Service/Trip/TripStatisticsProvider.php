<?php
    namespace Core\Service\Trip;

    use Core\Service\Statistics\KeyValuePair;
    use Core\Service\Statistics\Statistics;
    use Core\Service\Statistics\StatisticsKind;
    use Core\Service\Statistics\StatisticsName;
    use Core\Service\Statistics\StatisticsProvider;
    use Core\Service\Statistics\StatisticsType;
    use Core\Service\Statistics\StatisticsUnit;

    class TripStatisticsProvider implements StatisticsProvider {

        private readonly TripService $tripService;

        public function __construct(TripService $tripService) {
            $this->tripService = $tripService;
        }

        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            if ($statisticsKind === StatisticsKind::Fact) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $allTrips = $this->tripService->getRegularTrips(null, $start, $end, array(), TripSortingStrategy::OldestAscending);
                    $averageTripLength = round(array_sum(array_map(fn($trip) => $trip->getDaysCount(), $allTrips)) / max(count($allTrips), 1));
                    if ($averageTripLength > 0) {
                        $statistics[] = new Statistics(StatisticsName::AverageTripLength, $averageTripLength, StatisticsUnit::Days);
                    }
                }
            }            

            if ($statisticsKind === StatisticsKind::Standings) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {                    
                    $longestTrips = array_map(fn($trip) => new KeyValuePair($trip->getFullName(), $trip->getDaysCount()),
                        $this->tripService->getRegularTrips(null, $start, $end, array(), TripSortingStrategy::LongestAscending));
                    if (count($longestTrips) > 0) {
                        $statistics[] = new Statistics(StatisticsName::LongestTrips, $longestTrips, StatisticsUnit::Days);
                    }
                }
            }

            return $statistics;
        }
    }
?>