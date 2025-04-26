<?php
    namespace Service\Service\Fitness;

    class FitnessService {

        const FITNESS_RECORD_DURATION = 1800;

        private readonly FitnessMapper $fitnessMapper;

        private readonly \EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, \EventPublisher $eventPublisher) {
            $this->fitnessMapper = new FitnessMapper($databaseProvider);
            $this->eventPublisher = $eventPublisher;
        }

        public function getFitnessRecordTimestampsToUpdate() : array {
            return $this->fitnessMapper->selectFitnessRecordTimestampsToUpdate();
        }

        public function getFitnessRecordForDate(int $timestamp) : Fitness {
            return $this->fitnessMapper->selectFitnessRecordForInterval($timestamp, $timestamp + 86400);
        }

        public function updateFitnessRecord(int $timestamp, int $steps, int $minutes, float $calories, float $distance) : bool {
            $distance = $this->getCorrectedDistance($distance, $steps);
            
            $existingFitnessRecord = $this->fitnessMapper->selectFitnessRecord($timestamp);

            if ($existingFitnessRecord !== NULL && ($steps < $existingFitnessRecord->getSteps()
                || $minutes < $existingFitnessRecord->getMinutes()|| $distance < $existingFitnessRecord->getDistance())) {
                $this->fitnessMapper->updateFitnessRecordLastUpdate($timestamp);
                return FALSE;
            }

            $this->fitnessMapper->deleteFitnessRecord($timestamp);

            $fitnessRecord = new Fitness($steps, min($minutes, self::FITNESS_RECORD_DURATION / 60), $calories, $distance);
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