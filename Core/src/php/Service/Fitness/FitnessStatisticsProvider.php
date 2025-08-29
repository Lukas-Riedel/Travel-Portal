<?php
    namespace Core\Service\Fitness;

    use Core\Common\CommonConstants;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Service\Statistics\KeyValuePair;
    use Core\Service\Statistics\Statistics;
    use Core\Service\Statistics\StatisticsKind;
    use Core\Service\Statistics\StatisticsProvider;
    use Core\Service\Statistics\StatisticsType;
    use Core\Service\Statistics\StatisticsUnit;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;

    class FitnessStatisticsProvider implements StatisticsProvider {

        private const PLACES_AND_DATE_FORMAT = "%s @ %s";

        private const TOTAL_STEPS_COUNT_STATISTICS_NAME = "TOTAL_STEPS_COUNT";
        private const AVERAGE_STEPS_PER_DAY_STATISTICS_NAME = "AVERAGE_STEPS_PER_DAY";
        private const TOTAL_TIME_IN_MOTION_STATISTICS_NAME = "TOTAL_TIME_IN_MOTION";
        private const AVERAGE_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME = "AVERAGE_TIME_IN_MOTION_PER_DAY";
        private const MOST_STEPS_PER_DAY_STATISTICS_NAME = "MOST_STEPS_PER_DAY";
        private const LEAST_STEPS_PER_DAY_STATISTICS_NAME = "LEAST_STEPS_PER_DAY";
        private const MOST_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME = "MOST_TIME_IN_MOTION_PER_DAY";
        private const LEAST_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME = "LEAST_TIME_IN_MOTION_PER_DAY";
        private const MOST_AVERAGE_STEPS_PER_DAY_TRIPS_STATISTICS_NAME = "MOST_AVERAGE_STEPS_PER_DAY_TRIPS";
        private const LEAST_AVERAGE_STEPS_PER_DAY_TRIPS_STATISTICS_NAME = "LEAST_AVERAGE_STEPS_PER_DAY_TRIPS";
        private const MOST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS_STATISTICS_NAME = "MOST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS";
        private const LEAST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS_STATISTICS_NAME = "LEAST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS";

        private readonly FitnessService $fitnessService;

        private readonly PlaceService $placeService;
        private readonly TripService $tripService;

        public function __construct(FitnessService $fitnessService, PlaceService $placeService, TripService $tripService) {
            $this->fitnessService = $fitnessService;
            $this->placeService = $placeService;
            $this->tripService = $tripService;
        }

        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            if ($statisticsKind === StatisticsKind::Fact) {
                $totalFitness = $this->fitnessService->getFitnessRecordForInterval($start, $end);
                $averageFitness = $this->fitnessService->getAverageFitnessRecordForInterval($start, $end);

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year || $statisticsType === StatisticsType::Trip) {
                    if ($totalFitness->getSteps() > 0) {
                        $statistics[] = new Statistics(self::TOTAL_STEPS_COUNT_STATISTICS_NAME, $totalFitness->getSteps(), StatisticsUnit::Steps);
                    }
                    
                    if ($averageFitness->getSteps() > 0) {
                        $statistics[] = new Statistics(self::AVERAGE_STEPS_PER_DAY_STATISTICS_NAME, $averageFitness->getSteps(), StatisticsUnit::Steps);
                    }
                    
                    if ($totalFitness->getSeconds() > 0) {
                        $statistics[] = new Statistics(self::TOTAL_TIME_IN_MOTION_STATISTICS_NAME, $totalFitness->getSeconds(), StatisticsUnit::Duration);
                    }
                    
                    if ($averageFitness->getSeconds() > 0) {
                        $statistics[] = new Statistics(self::AVERAGE_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME, $averageFitness->getSeconds(), StatisticsUnit::Duration);
                    }
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {                
                $mostStepsPerDayRecords = $this->getStandingsStatisticsForDayRecords($this->fitnessService->getTimeBasedFitnessRecordsPerDayForInterval($start, $end, FitnessSortingStrategy::StepsDescending),
                    fn($record) => $record->getFitness()->getSteps(), $categoryId);
                if (count($mostStepsPerDayRecords) > 0) {
                    $statistics[] = new Statistics(self::MOST_STEPS_PER_DAY_STATISTICS_NAME, $mostStepsPerDayRecords, StatisticsUnit::Steps);
                }
                
                $leastStepsPerDayRecords = $this->getStandingsStatisticsForDayRecords($this->fitnessService->getTimeBasedFitnessRecordsPerDayForInterval($start, $end, FitnessSortingStrategy::StepsAscending),
                    fn($record) => $record->getFitness()->getSteps(), $categoryId);
                if (count($leastStepsPerDayRecords) > 0) {
                    $statistics[] = new Statistics(self::LEAST_STEPS_PER_DAY_STATISTICS_NAME, $leastStepsPerDayRecords, StatisticsUnit::Steps);
                }
                
                $mostTimeInMotionPerDayRecords = $this->getStandingsStatisticsForDayRecords($this->fitnessService->getTimeBasedFitnessRecordsPerDayForInterval($start, $end, FitnessSortingStrategy::TimeInMotionDescending),
                    fn($record) => $record->getFitness()->getSeconds(), $categoryId);
                if (count($mostTimeInMotionPerDayRecords) > 0) {
                    $statistics[] = new Statistics(self::MOST_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME, $mostTimeInMotionPerDayRecords, StatisticsUnit::Duration);
                }
                
                $leastTimeInMotionPerDayRecords = $this->getStandingsStatisticsForDayRecords($this->fitnessService->getTimeBasedFitnessRecordsPerDayForInterval($start, $end, FitnessSortingStrategy::TimeInMotionAscending),
                    fn($record) => $record->getFitness()->getSeconds(), $categoryId);
                if (count($leastTimeInMotionPerDayRecords) > 0) {
                    $statistics[] = new Statistics(self::LEAST_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME, $leastTimeInMotionPerDayRecords, StatisticsUnit::Duration);
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $tripFitnessRecords = array_map(fn($trip) => new TripFitness($trip, $this->fitnessService->getAverageFitnessRecordForInterval($trip->getStart(), $trip->getEnd())),
                        array_filter($this->tripService->getRegularTrips(null, $start, $end, array(), TripSortingStrategy::OldestAscending), fn($trip) => !$this->tripService->isDayTripsTrip($trip)));
                    if (count($tripFitnessRecords) > 0) {
                        $mostAverageSteps = $this->getStandingsStatisticsForTripRecords($tripFitnessRecords, fn($fitness) => $fitness->getSteps());
                        $statistics[] = new Statistics(self::MOST_AVERAGE_STEPS_PER_DAY_TRIPS_STATISTICS_NAME, $mostAverageSteps, StatisticsUnit::Steps);
                        $statistics[] = new Statistics(self::LEAST_AVERAGE_STEPS_PER_DAY_TRIPS_STATISTICS_NAME, array_reverse($mostAverageSteps), StatisticsUnit::Steps);
                        
                        $mostTimeInMotion = $this->getStandingsStatisticsForTripRecords($tripFitnessRecords, fn($fitness) => $fitness->getSeconds());
                        $statistics[] = new Statistics(self::MOST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS_STATISTICS_NAME, $mostTimeInMotion, StatisticsUnit::Duration);
                        $statistics[] = new Statistics(self::LEAST_AVERAGE_TIME_IN_MOTION_PER_DAY_TRIPS_STATISTICS_NAME, array_reverse($mostTimeInMotion), StatisticsUnit::Duration);
                    }
                }
            }

            return $statistics;
        }

        private function getStandingsStatisticsForTripRecords(array $records, callable $valueSelector) : array {
            $standings = array_map(fn($record) => new KeyValuePair($record->getTrip()->getFullName(), $valueSelector($record->getFitness())), $records);
            usort($standings, fn($a, $b) => $b->getValue() <=> $a->getValue());
            return $standings;
        }

        private function getStandingsStatisticsForDayRecords(array $records, callable $valueSelector, ?string $categoryId) : array {
            return array_filter(array_map(function($record) use(&$categoryId, &$valueSelector) {
                $places = array_filter($this->placeService->getRegularPlaces($categoryId, null, null, null, null, null, null, $record->getTimestamp(),
                    $record->getTimestamp() + CommonConstants::ONE_DAY_SECONDS, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending),
                    fn($place) => count($place->getDates()) > 0);
                return empty($places) ? null : new KeyValuePair(sprintf(self::PLACES_AND_DATE_FORMAT,
                    implode(", ", array_map(fn($place) => $place->getName(), $places)),
                    date(CommonConstants::DMY_DATE_FORMAT, $record->getTimestamp())), $valueSelector($record));
            }, $records), fn($statistics) => $statistics !== null);
        }
    }
?>