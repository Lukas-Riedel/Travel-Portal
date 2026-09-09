<?php
    namespace Core\Service\Fitness;

    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Monolog\Logger;

    class FitnessService {

        private const MIN_DISTANCE_PER_STEP_COEFFICIENT = 0.85;
        private const MAX_DISTANCE_PER_STEP_COEFFICIENT = 1.15;
        private const MIN_DISTANCE_PER_STEP_FLOOR = 0.5;
        private const MAX_DISTANCE_PER_STEP_CEILING = 1.5;

        private readonly FitnessMapper $fitnessMapper;
        private readonly EventPublisher $eventPublisher;
        private readonly TransactionManager $transactionManager;
        private readonly Logger $logger;

        private readonly float $allowOverwriteThresholdCoefficient;
        private readonly int $allowOverwriteStepsThreshold;
        private readonly float $allowOverwriteDistanceThreshold;
        private readonly int $allowOverwriteDurationThreshold;
        private readonly int $stepsPerMinuteThreshold;

        public function __construct(DatabaseClient $databaseClient, EventPublisher $eventPublisher, Logger $logger, float $allowOverwriteThresholdCoefficient,
            int $allowOverwriteStepsThreshold, float $allowOverwriteDistanceThreshold, int $allowOverwriteDurationThreshold, int $updateThresholdDays, int $stepsPerMinuteThreshold) {
            $this->fitnessMapper = new FitnessMapper($databaseClient, $updateThresholdDays);
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
            $this->logger = $logger;
            $this->allowOverwriteThresholdCoefficient = $allowOverwriteThresholdCoefficient;
            $this->allowOverwriteStepsThreshold = $allowOverwriteStepsThreshold < 0 ? PHP_INT_MAX : $allowOverwriteStepsThreshold;
            $this->allowOverwriteDistanceThreshold = $allowOverwriteDistanceThreshold < 0 ? PHP_INT_MAX : $allowOverwriteDistanceThreshold;
            $this->allowOverwriteDurationThreshold = $allowOverwriteDurationThreshold < 0 ? PHP_INT_MAX : $allowOverwriteDurationThreshold;
            $this->stepsPerMinuteThreshold = $stepsPerMinuteThreshold;
        }
        
        public function getConflictingFitnessRecords() : array {
            return $this->fitnessMapper->selectConflictingFitnessRecords();
        }

        public function getAllValidFitnessRecordTimestamps() : array {
            return $this->fitnessMapper->selectAllValidFitnessRecordTimestamps();
        }

        public function getFitnessRecordForInterval(int $start, int $end) : Fitness {
            return $this->fitnessMapper->selectFitnessRecordForInterval($start, $end);
        }

        public function getAverageFitnessRecordForInterval(int $start, int $end) : Fitness {
            return $this->fitnessMapper->selectAverageFitnessRecordForInterval($start, $end);
        }

        public function getTimeBasedFitnessRecordsPerDayForInterval(int $start, int $end, FitnessSortingStrategy $fitnessSortingStrategy) : array {
            return $this->fitnessMapper->selectTimeBasedFitnessRecordsPerDayForInterval(CommonConstants::ONE_DAY_SECONDS * floor($start / CommonConstants::ONE_DAY_SECONDS),
                $end, $fitnessSortingStrategy);
        }

        public function updateFitnessRecord(int $timestamp, int $steps, int $seconds, float $distance, bool $forceUpdate = false) : bool {
            $end = $timestamp + CommonConstants::FITNESS_RECORD_DURATION_SECONDS;
            if ($end > time()) {
                throw new \RuntimeException("Unable to update a fitness record for an unfinished interval ($timestamp - $end)");
            }

            $distance = $this->getCorrectedDistance($distance, $steps);
            $seconds = $this->getCorrectedDuration($seconds, $steps);
            
            $existingFitnessRecord = $this->fitnessMapper->selectFitnessRecord($timestamp);
            $fitnessRecord = new TimeBasedFitness($timestamp, $steps, min($seconds, CommonConstants::FITNESS_RECORD_DURATION_SECONDS), $distance);

            if ($existingFitnessRecord !== null && ($existingFitnessRecord->getSteps() === 0 || $existingFitnessRecord->getSeconds() === 0 || round($existingFitnessRecord->getDistance(), 3) === 0.0)) {
                $forceUpdate = true;
            }
            
            if (!$forceUpdate && $existingFitnessRecord !== null && (
                ($existingFitnessRecord->getSteps() > $this->allowOverwriteStepsThreshold && $steps < $existingFitnessRecord->getSteps() * $this->allowOverwriteThresholdCoefficient)
                || ($existingFitnessRecord->getSeconds() > $this->allowOverwriteDurationThreshold && $seconds < $existingFitnessRecord->getSeconds() * $this->allowOverwriteThresholdCoefficient)
                || (round($existingFitnessRecord->getDistance(), 3) > $this->allowOverwriteDistanceThreshold && round($distance, 3) < round($existingFitnessRecord->getDistance(), 3) * $this->allowOverwriteThresholdCoefficient))) {
                $context = array(
                    "steps" => array(
                        "actual" => $steps,
                        "existing" => $existingFitnessRecord->getSteps(),
                    ),
                    "seconds" => array(
                        "actual" => $seconds,
                        "existing" => $existingFitnessRecord->getSeconds(),
                    ),
                    "distance" => array(
                        "actual" => $distance,
                        "existing" => $existingFitnessRecord->getDistance(),
                    ),
                );

                $this->logger->warning("The provided fitness record for timestamp '{$timestamp}' would override already existing higher values and will therefore not be updated.", $context);

                $this->transactionManager->executeAtomically(function() use(&$fitnessRecord, &$timestamp) {
                    $this->fitnessMapper->updateFitnessRecordLastUpdate($timestamp);
                    $this->fitnessMapper->deleteConflictingFitnessRecord($timestamp);
                    $this->fitnessMapper->insertConflictingFitnessRecord($fitnessRecord);
                });
                
                return false;
            }

            $this->transactionManager->executeAtomically(function() use(&$fitnessRecord, &$timestamp, &$end) {
                $this->fitnessMapper->deleteConflictingFitnessRecord($timestamp);
                $this->fitnessMapper->deleteFitnessRecord($timestamp);
                $this->fitnessMapper->insertFitnessRecord($fitnessRecord);

                $this->eventPublisher->publish(Event::FitnessDataUpdated($timestamp, $end));
            });

            return true;
        }

        public function removeUnreferencedFitnessRecords(array $allRequiredTimestamps) : void {
            global $logger;

            $allRequiredTimestampsMap = array_flip($allRequiredTimestamps);
            $allTimestamps = $this->fitnessMapper->selectAllFitnessRecordTimestamps();
            
            foreach ($allTimestamps as &$timestamp) {
                if (!isset($allRequiredTimestampsMap[$timestamp])) {
                    $this->fitnessMapper->deleteFitnessRecord($timestamp);
                    $logger->info("Removing stale fitness record for timestamp {$timestamp}...");
                }
            }
        }

        private function getCorrectedDistance(float $distance, int $steps) : float {
            if ($steps > 0 && (($distance / $steps < max(self::MIN_DISTANCE_PER_STEP_FLOOR, $this->fitnessMapper->selectMinimumDistancePerStep() * self::MIN_DISTANCE_PER_STEP_COEFFICIENT))
                || ($distance / $steps > min($this->fitnessMapper->selectMaximumDistancePerStep() * self::MAX_DISTANCE_PER_STEP_COEFFICIENT, self::MAX_DISTANCE_PER_STEP_CEILING)))) {
                return $steps * $this->fitnessMapper->selectAverageDistancePerStep();
            }
            return $distance;
        }

        private function getCorrectedDuration(int $seconds, int $steps) : int {
            if ($seconds > 0 && $steps > 0 && ($steps / ($seconds / 60.0)) < $this->stepsPerMinuteThreshold) {
                return min($seconds, round($steps * $this->fitnessMapper->selectAverageSecondsPerStep()));
            }

            return $seconds;
        }
    }
?>