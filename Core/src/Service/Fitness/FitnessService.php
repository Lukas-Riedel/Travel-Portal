<?php
    namespace Core\Service\Fitness;

    use Core\Common\CommonConstants;
    use Monolog\Logger;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;

    class FitnessService {

        // TODO: Extract to an environment variable.
        private const STEPS_PER_MINUTE_THRESHOLD = 30;

        private readonly FitnessMapper $fitnessMapper;
        private readonly EventPublisher $eventPublisher;        
        private readonly TransactionManager $transactionManager;
        private readonly Logger $logger;
        
        private readonly float $allowOverwriteThresholdCoefficient;
        private readonly int $allowOverwriteStepsThreshold;
        private readonly float $allowOverwriteDistanceThreshold;
        private readonly int $allowOverwriteDurationThreshold;

        public function __construct(DatabaseClient $databaseClient, EventPublisher $eventPublisher, Logger $logger, float $allowOverwriteThresholdCoefficient,
            int $allowOverwriteStepsThreshold, float $allowOverwriteDistanceThreshold, int $allowOverwriteDurationThreshold, int $updateThresholdDays) {
            $this->fitnessMapper = new FitnessMapper($databaseClient, $updateThresholdDays);
            $this->eventPublisher = $eventPublisher;
            $this->transactionManager = $databaseClient;
            $this->logger = $logger;
            $this->allowOverwriteThresholdCoefficient = $allowOverwriteThresholdCoefficient;
            $this->allowOverwriteStepsThreshold = $allowOverwriteStepsThreshold < 0 ? PHP_INT_MAX : $allowOverwriteStepsThreshold;
            $this->allowOverwriteDistanceThreshold = $allowOverwriteDistanceThreshold < 0 ? PHP_INT_MAX : $allowOverwriteDistanceThreshold;
            $this->allowOverwriteDurationThreshold = $allowOverwriteDurationThreshold < 0 ? PHP_INT_MAX : $allowOverwriteDurationThreshold;
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
            $fitnessRecord = new Fitness($steps, min($seconds, CommonConstants::FITNESS_RECORD_DURATION_SECONDS), $distance);
            
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
                    $this->fitnessMapper->insertConflictingFitnessRecord($fitnessRecord, $timestamp);                    
                });
                
                return false;
            }

            $this->transactionManager->executeAtomically(function() use(&$fitnessRecord, &$timestamp, &$end) {
                $this->fitnessMapper->deleteConflictingFitnessRecord($timestamp);
                $this->fitnessMapper->deleteFitnessRecord($timestamp);
                $this->fitnessMapper->insertFitnessRecord($fitnessRecord, $timestamp);

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
            // TODO: Create constants for the magic numbers.
            if ($steps > 0 && (($distance / $steps < max(0.5, $this->fitnessMapper->selectMinimumDistancePerStep() * 0.85))
                || ($distance / $steps > min($this->fitnessMapper->selectMaximumDistancePerStep() * 1.15, 1.5)))) {
                return $steps * $this->fitnessMapper->selectAverageDistancePerStep();
            }
            return $distance;
        }

        private function getCorrectedDuration(int $seconds, int $steps) : int {
            if ($seconds > 0 && $steps > 0 && ($steps / ($seconds / 60.0)) < self::STEPS_PER_MINUTE_THRESHOLD) {
                return min($seconds, round($steps * $this->fitnessMapper->selectAverageSecondsPerStep()));
            }

            return $seconds;
        }
    }
?>