<?php
    namespace Core\Service\Fitness;

    use Core\Common\CommonConstants;
    use Monolog\Logger;
    use Core\Service\Configuration\ConfigurationService;
use RuntimeException;

    class FitnessService {

        private readonly FitnessMapper $fitnessMapper;

        private readonly \EventPublisher $eventPublisher;

        private readonly Logger $logger;

        public function __construct(\DatabaseProvider $databaseProvider, \EventPublisher $eventPublisher, ConfigurationService $configurationService, Logger $logger) {
            $this->fitnessMapper = new FitnessMapper($databaseProvider, $configurationService);
            $this->eventPublisher = $eventPublisher;
            $this->logger = $logger;
        }
        
        public function getConflictingFitnessRecords() : array {
            return $this->fitnessMapper->selectConflictingFitnessRecords();
        }

        public function getFitnessRecordTimestampsToUpdate(int $limit) : array {
            return $this->fitnessMapper->selectFitnessRecordTimestampsToUpdate($limit);
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
                throw new RuntimeException("Unable to update a fitness record for an unfinished interval (" . $timestamp  . " - " . $end . ")");
            }

            $distance = $this->getCorrectedDistance($distance, $steps);
            
            $existingFitnessRecord = $this->fitnessMapper->selectFitnessRecord($timestamp);
            $fitnessRecord = new Fitness($steps, min($seconds, CommonConstants::FITNESS_RECORD_DURATION_SECONDS), $distance);
            
            $this->fitnessMapper->deleteConflictingFitnessRecord($timestamp);

            if (!$forceUpdate && $existingFitnessRecord !== null && ($steps < $existingFitnessRecord->getSteps()
                || $seconds < $existingFitnessRecord->getSeconds()|| round($distance, 3) < round($existingFitnessRecord->getDistance(), 3))) {
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

                $this->fitnessMapper->updateFitnessRecordLastUpdate($timestamp);                
                $this->fitnessMapper->insertConflictingFitnessRecord($fitnessRecord, $timestamp);
                
                // TODO: Return false after reworking transactional consistency (all statements in this block would be rollbacked in case of returning false).
                return true;
            }

            $this->fitnessMapper->deleteFitnessRecord($timestamp);
            $this->fitnessMapper->insertFitnessRecord($fitnessRecord, $timestamp);

            $this->eventPublisher->publishFitnessDataUpdatedEvent($timestamp, $end);

            return true;
        }

        public function removeStaleFitnessRecords() : void {
            $this->fitnessMapper->deleteStaleFitnessRecords();
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