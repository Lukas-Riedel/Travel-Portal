<?php
    namespace Service\Service\Trip;

    use Service\Service\Statistics\KeyValuePair;
    use Service\Service\Statistics\Statistics;
    use Service\Service\Statistics\StatisticsKind;
    use Service\Service\Statistics\StatisticsProvider;
    use Service\Service\Statistics\StatisticsType;
    use Service\Service\Statistics\StatisticsUnit;

    class TripStatisticsProvider implements StatisticsProvider {

        private const AVERAGE_TRIP_LENGTH_STATISTICS_NAME = "AVERAGE_TRIP_LENGTH";
        private const LONGEST_TRIPS_STATISTICS_NAME = "LONGEST_TRIPS";
        private const SHORTEST_TRIPS_STATISTICS_NAME = "SHORTEST_TRIPS";

        private readonly TripService $tripService;

        public function __construct(TripService $tripService) {
            $this->tripService = $tripService;
        }

        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            if ($statisticsKind === StatisticsKind::Fact) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $allTrips = $this->filterDayTripsTrips($this->tripService->getRegularTrips(NULL, $start, $end, array(), TripSortingStrategy::Default));
                    $averageTripLength = round(array_sum(array_map(fn($trip) => $trip->getDays()->getTotal(), $allTrips)) / max(count($allTrips), 1));
                    if ($averageTripLength > 0) {
                        $statistics[] = new Statistics(self::AVERAGE_TRIP_LENGTH_STATISTICS_NAME, $averageTripLength, StatisticsUnit::Days);
                    }
                }
            }            

            if ($statisticsKind === StatisticsKind::Standings) {
                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {                    
                    $longestTrips = array_map(fn($trip) => new KeyValuePair($trip->getFullName(), $trip->getDays()->getTotal()),
                        $this->filterDayTripsTrips($this->tripService->getRegularTrips(NULL, $start, $end, array(), TripSortingStrategy::LongestAscending)));
                    if (count($longestTrips) > 0) {
                        $statistics[] = new Statistics(self::LONGEST_TRIPS_STATISTICS_NAME, $longestTrips, StatisticsUnit::Days);
                    }
                    
                    $shortestTrips = array_map(fn($trip) => new KeyValuePair($trip->getFullName(), $trip->getDays()->getTotal()),
                        $this->filterDayTripsTrips($this->tripService->getRegularTrips(NULL, $start, $end, array(), TripSortingStrategy::ShortestAscending)));
                    if (count($shortestTrips) > 0) {
                        $statistics[] = new Statistics(self::SHORTEST_TRIPS_STATISTICS_NAME, $shortestTrips, StatisticsUnit::Days);
                    }
                }
            }

            return $statistics;
        }
        

        private function filterDayTripsTrips(array $trips) : array {
            return array_filter($trips, fn($trip) => !$this->tripService->isDayTripsTrip($trip));
        }
    }
?>