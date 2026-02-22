<?php
    namespace Core\Service\Fitness;

    use Core\Common\CommonConstants;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Service\Statistics\KeyValuePair;
    use Core\Service\Statistics\Statistics;
    use Core\Service\Statistics\StatisticsKind;
    use Core\Service\Statistics\StatisticsName;
    use Core\Service\Statistics\StatisticsProvider;
    use Core\Service\Statistics\StatisticsType;
    use Core\Service\Statistics\StatisticsUnit;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;

    class FitnessStatisticsProvider implements StatisticsProvider {

        private const PLACES_AND_DATE_FORMAT = "%s @ %s";

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
                        $statistics[] = new Statistics(StatisticsName::TotalStepsCount, $totalFitness->getSteps(), StatisticsUnit::Steps);
                    }
                    
                    if ($averageFitness->getSteps() > 0) {
                        $statistics[] = new Statistics(StatisticsName::AverageStepsPerDay, $averageFitness->getSteps(), StatisticsUnit::Steps);
                    }
                    
                    if ($totalFitness->getSeconds() > 0) {
                        $statistics[] = new Statistics(StatisticsName::TotalTimeInMotion, $totalFitness->getSeconds(), StatisticsUnit::Duration);
                    }
                    
                    if ($averageFitness->getSeconds() > 0) {
                        $statistics[] = new Statistics(StatisticsName::AverageTimeInMotionPerDay, $averageFitness->getSeconds(), StatisticsUnit::Duration);
                    }
                }
            }
            
            if ($statisticsKind === StatisticsKind::Standings) {               
                $placesCache = array(); 
                $mostStepsPerDayRecords = $this->getStandingsStatisticsForDayRecords($placesCache, $this->fitnessService->getTimeBasedFitnessRecordsPerDayForInterval($start, $end, FitnessSortingStrategy::StepsDescending),
                    fn($record) => $record->getFitness()->getSteps(), $categoryId, $statisticsType === StatisticsType::Trip ? $entityId : null);
                if (count($mostStepsPerDayRecords) > 0) {
                    $statistics[] = new Statistics(StatisticsName::MostStepsPerDay, $mostStepsPerDayRecords, StatisticsUnit::Steps);
                }
                
                $leastStepsPerDayRecords = $this->getStandingsStatisticsForDayRecords($placesCache, $this->fitnessService->getTimeBasedFitnessRecordsPerDayForInterval($start, $end, FitnessSortingStrategy::StepsAscending),
                    fn($record) => $record->getFitness()->getSteps(), $categoryId, $statisticsType === StatisticsType::Trip ? $entityId : null);
                if (count($leastStepsPerDayRecords) > 0) {
                    $statistics[] = new Statistics(StatisticsName::LeastStepsPerDay, $leastStepsPerDayRecords, StatisticsUnit::Steps);
                }
                
                $mostTimeInMotionPerDayRecords = $this->getStandingsStatisticsForDayRecords($placesCache, $this->fitnessService->getTimeBasedFitnessRecordsPerDayForInterval($start, $end, FitnessSortingStrategy::TimeInMotionDescending),
                    fn($record) => $record->getFitness()->getSeconds(), $categoryId, $statisticsType === StatisticsType::Trip ? $entityId : null);
                if (count($mostTimeInMotionPerDayRecords) > 0) {
                    $statistics[] = new Statistics(StatisticsName::MostTimeInMotionPerDay, $mostTimeInMotionPerDayRecords, StatisticsUnit::Duration);
                }
                
                $leastTimeInMotionPerDayRecords = $this->getStandingsStatisticsForDayRecords($placesCache, $this->fitnessService->getTimeBasedFitnessRecordsPerDayForInterval($start, $end, FitnessSortingStrategy::TimeInMotionAscending),
                    fn($record) => $record->getFitness()->getSeconds(), $categoryId, $statisticsType === StatisticsType::Trip ? $entityId : null);
                if (count($leastTimeInMotionPerDayRecords) > 0) {
                    $statistics[] = new Statistics(StatisticsName::LeastTimeInMotionPerDay, $leastTimeInMotionPerDayRecords, StatisticsUnit::Duration);
                }

                if ($statisticsType === StatisticsType::Overall || $statisticsType === StatisticsType::Year) {
                    $tripFitnessRecords = array_map(fn($trip) => new TripFitness($trip, $this->fitnessService->getAverageFitnessRecordForInterval($trip->getStart(), $trip->getEnd())),
                        $this->tripService->getRegularTrips(null, $start, $end, array(), TripSortingStrategy::OldestAscending));
                    if (count($tripFitnessRecords) > 0) {
                        $mostAverageSteps = $this->getStandingsStatisticsForTripRecords($tripFitnessRecords, fn($fitness) => $fitness->getSteps());
                        $statistics[] = new Statistics(StatisticsName::MostAverageStepsPerDayTrips, $mostAverageSteps, StatisticsUnit::Steps);
                        $statistics[] = new Statistics(StatisticsName::LeastAverageStepsPerDayTrips, array_reverse($mostAverageSteps), StatisticsUnit::Steps);
                        
                        $mostTimeInMotion = $this->getStandingsStatisticsForTripRecords($tripFitnessRecords, fn($fitness) => $fitness->getSeconds());
                        $statistics[] = new Statistics(StatisticsName::MostAverageTimeInMotionPerDayTrips, $mostTimeInMotion, StatisticsUnit::Duration);
                        $statistics[] = new Statistics(StatisticsName::LeastAverageTimeInMotionPerDayTrips, array_reverse($mostTimeInMotion), StatisticsUnit::Duration);
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

        private function getStandingsStatisticsForDayRecords(array &$placesCache, array $records, callable $valueSelector, ?string $categoryId, ?string $tripId) : array {
            if (empty($records)) {
                return array();
            }
            
            $timestamps = array_map(fn($record) => $record->getTimestamp(), $records);
            $minTimestamp = min($timestamps);
            $maxTimestamp = max($timestamps) + CommonConstants::ONE_DAY_SECONDS;

            $cacheKey = ($categoryId ?? "null") . "_" . ($tripId ?? "null") . "_" . $minTimestamp . "_" . $maxTimestamp;
            if (!isset($placesCache[$cacheKey])) {
                $allPlaces = $this->placeService->getRegularPlaces($categoryId, null, $tripId, null, null, null, null, $minTimestamp, 
                    $maxTimestamp, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

                $placesByDay = array();
                foreach ($allPlaces as $place) {
                    foreach ($place->getDates() as $date) {
                        $dayKey = date(CommonConstants::DMY_DATE_FORMAT, $date->getStart());
                        $placesByDay[$dayKey] ??= array();
                        $placesByDay[$dayKey][] = $place;
                    }
                }

                $placesCache[$cacheKey] = $placesByDay;
            }

            return array_filter(array_map(function($record) use(&$placesCache, &$cacheKey, &$valueSelector) {
                $dayKey = date(CommonConstants::DMY_DATE_FORMAT, $record->getTimestamp());
                $places = $placesCache[$cacheKey][$dayKey] ?? array();
                if (empty($places)) {
                    return null;
                }
                $value = $valueSelector($record);
                if ($value === 0) {
                    return null;
                }
                return new KeyValuePair(sprintf(self::PLACES_AND_DATE_FORMAT,
                    implode(", ", array_map(fn($place) => $place->getName(), $places)),
                    date(CommonConstants::DMY_DATE_FORMAT, $record->getTimestamp())), $value);
            }, $records), fn($statistics) => $statistics !== null);
        }
    }
?>