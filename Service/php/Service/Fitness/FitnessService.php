<?php
    namespace Service\Service\Fitness;

    use Service\Service\Statistics\Statistics;
    use Service\Service\Statistics\StatisticsKind;
    use Service\Service\Statistics\StatisticsProvider;
    use Service\Service\Statistics\StatisticsType;
    use Service\Service\Statistics\StatisticsUnit;

    class FitnessService implements StatisticsProvider {

        const FITNESS_RECORD_DURATION = 1800;

        private const TOTAL_STEPS_COUNT_STATISTICS_NAME = "TOTAL_STEPS_COUNT";
        private const AVERAGE_STEPS_PER_DAY_STATISTICS_NAME = "AVERAGE_STEPS_PER_DAY";
        private const TOTAL_TIME_IN_MOTION_STATISTICS_NAME = "TOTAL_TIME_IN_MOTION";
        private const AVERAGE_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME = "AVERAGE_TIME_IN_MOTION_PER_DAY";
        private const MOST_STEPS_PER_DAY_STATISTICS_NAME = "MOST_STEPS_PER_DAY";
        private const LEAST_STEPS_PER_DAY_STATISTICS_NAME = "LEAST_STEPS_PER_DAY";
        private const MOST_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME = "MOST_TIME_IN_MOTION_PER_DAY";
        private const LEAST_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME = "LEAST_TIME_IN_MOTION_PER_DAY";

        private readonly FitnessMapper $fitnessMapper;

        private readonly \EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, \EventPublisher $eventPublisher) {
            $this->fitnessMapper = new FitnessMapper($databaseProvider);
            $this->eventPublisher = $eventPublisher;
        }
        
        public function fetchStatistics(StatisticsType $statisticsType, StatisticsKind $statisticsKind,
            int $start, int $end, ?string $categoryId, ?string $entityId) : array {
            $statistics = array();

            if ($statisticsKind === StatisticsKind::Fact) {
                $totalFitness = $this->fitnessMapper->selectFitnessRecordForInterval($start, $end);
                $averageFitness = $this->fitnessMapper->selectAverageFitnessRecordForInterval($start, $end);

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
                $mostStepsPerDay = $this->fitnessMapper->selectMostStepsPerDay($start, $end, $categoryId);
                if (count($mostStepsPerDay) > 0) {
                    $statistics[] = new Statistics(self::MOST_STEPS_PER_DAY_STATISTICS_NAME, $mostStepsPerDay, StatisticsUnit::Steps);
                }
                
                $leastStepsPerDay = $this->fitnessMapper->selectLeastStepsPerDay($start, $end, $categoryId);
                if (count($leastStepsPerDay) > 0) {
                    $statistics[] = new Statistics(self::LEAST_STEPS_PER_DAY_STATISTICS_NAME, $leastStepsPerDay, StatisticsUnit::Steps);
                }
                
                $mostSecondsInMotionPerDay = $this->fitnessMapper->selectMostSecondsInMotionPerDay($start, $end, $categoryId);
                if (count($mostSecondsInMotionPerDay) > 0) {
                    $statistics[] = new Statistics(self::MOST_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME, $mostSecondsInMotionPerDay, StatisticsUnit::Duration);
                }
                
                $leastSecondsInMotionPerDay = $this->fitnessMapper->selectLeastSecondsInMotionPerDay($start, $end, $categoryId);
                if (count($leastSecondsInMotionPerDay) > 0) {
                    $statistics[] = new Statistics(self::LEAST_TIME_IN_MOTION_PER_DAY_STATISTICS_NAME, $leastSecondsInMotionPerDay, StatisticsUnit::Duration);
                }
            }

            return $statistics;
        }

        public function getFitnessRecordTimestampsToUpdate() : array {
            return $this->fitnessMapper->selectFitnessRecordTimestampsToUpdate();
        }

        public function getFitnessRecordForDate(int $timestamp) : Fitness {
            return $this->fitnessMapper->selectFitnessRecordForInterval($timestamp, $timestamp + 86400);
        }

        public function getAverageFitnessRecordForInterval(int $start, int $end) : Fitness {
            return $this->fitnessMapper->selectAverageFitnessRecordForInterval($start, $end);
        }

        public function updateFitnessRecord(int $timestamp, int $steps, int $seconds, float $calories, float $distance) : bool {
            $distance = $this->getCorrectedDistance($distance, $steps);
            
            $existingFitnessRecord = $this->fitnessMapper->selectFitnessRecord($timestamp);

            if ($existingFitnessRecord !== NULL && ($steps < $existingFitnessRecord->getSteps()
                || $seconds < $existingFitnessRecord->getSeconds()|| $distance < $existingFitnessRecord->getDistance())) {
                $this->fitnessMapper->updateFitnessRecordLastUpdate($timestamp);
                return FALSE;
            }

            $this->fitnessMapper->deleteFitnessRecord($timestamp);

            $fitnessRecord = new Fitness($steps, min($seconds, self::FITNESS_RECORD_DURATION), $calories, $distance);
            $this->fitnessMapper->insertFitnessRecord($fitnessRecord, $timestamp);

            $this->eventPublisher->publishFitnessDataUpdatedEvent($timestamp, $timestamp + self::FITNESS_RECORD_DURATION);

            return TRUE;
        }

        private function getCorrectedDistance(float $distance, int $steps) : float {
            // Distance is recorded incorrectly, scale steps by average step length.
            if ($steps > 0 && (($distance / $steps < max(0.5, $this->fitnessMapper->selectMinimumDistancePerStep() * 0.85))
                || ($distance / $steps > min($this->fitnessMapper->selectMaximumDistancePerStep() > 1.15, 1.5)))) {
                return $steps * $this->fitnessMapper->selectAverageDistancePerStep();
            }
            return $distance;
        }
    }
?>