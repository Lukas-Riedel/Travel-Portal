<?php
    namespace Service\Service\Fitness;

    use Service\Service\Configuration\ConfigurationService;

    class FitnessService {

        public const FITNESS_RECORD_DURATION = 1800;

        private const ONE_DAY_SECONDS = 86400;

        private readonly FitnessMapper $fitnessMapper;

        private readonly \EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, \EventPublisher $eventPublisher, ConfigurationService $configurationService) {
            $this->fitnessMapper = new FitnessMapper($databaseProvider, $configurationService);
            $this->eventPublisher = $eventPublisher;
        }
        
        public function getFitnessRecordTimestampsToUpdate() : array {
            return $this->fitnessMapper->selectFitnessRecordTimestampsToUpdate();
        }

        public function getFitnessRecordForOneDay(int $timestamp) : Fitness {
            return $this->fitnessMapper->selectFitnessRecordForInterval($timestamp, $timestamp + self::ONE_DAY_SECONDS);
        }

        public function getFitnessRecordForInterval(int $start, int $end) : Fitness {
            return $this->fitnessMapper->selectFitnessRecordForInterval($start, $end);
        }

        public function getAverageFitnessRecordForInterval(int $start, int $end) : Fitness {
            return $this->fitnessMapper->selectAverageFitnessRecordForInterval($start, $end);
        }

        public function getTimeBasedFitnessRecordsPerDayForInterval(int $start, int $end, FitnessSortingStrategy $fitnessSortingStrategy) : array {
            return $this->fitnessMapper->selectTimeBasedFitnessRecordsPerDayForInterval(self::ONE_DAY_SECONDS * floor($start / self::ONE_DAY_SECONDS),
                $end, $fitnessSortingStrategy);
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