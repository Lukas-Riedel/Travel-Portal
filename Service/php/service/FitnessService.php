<?php
    require_once(dirname(__FILE__) . "/FitnessMapper.php");
    require_once(dirname(__FILE__) . "/../model/Fitness.php");

    class FitnessService {
        
        private const FETCH_FITNESS_ACTION_NAME = "FETCH_FITNESS";

        private readonly FitnessMapper $fitnessMapper;

        private readonly ConfigurationService $configurationService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(DatabaseProvider $databaseProvider, ConfigurationService $configurationService,
            EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->fitnessMapper = new FitnessMapper($databaseProvider);
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function getFitnessRecordForDay(int $timestamp) : Fitness {
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

            $fitnessRecordDuration = $this->configurationService->getConfigurationForType("fitnessRecordDuration");
            $fitnessRecord = new Fitness($steps, min($minutes, $fitnessRecordDuration / 60), $calories, $distance);
            $this->fitnessMapper->insertFitnessRecord($fitnessRecord, $timestamp);

            $this->eventPublisher->publishFitnessDataUpdatedEvent($timestamp, $timestamp + $fitnessRecordDuration);

            return TRUE;
        }

        public function onSchedulerTriggered($message) : void {
            if ($message["action"] === self::FETCH_FITNESS_ACTION_NAME
                && $message["timeSinceLastExecution"] > $this->configurationService->getConfigurationForType("fitnessRecordDuration")) {
                $fitnessRecordDuration = $this->configurationService->getConfigurationForType("fitnessRecordDuration");
                $timestampsToUpdate = $this->fitnessMapper->selectFitnessRecordTimestampsToUpdate();

                foreach ($timestampsToUpdate as &$timestampToUpdate) {
                    $this->eventPublisher->publishFitnessActivityDetectedEvent($timestampToUpdate, $timestampToUpdate + $fitnessRecordDuration);
                }
                        
                $this->scheduler->recordEventsTriggered(self::FETCH_FITNESS_ACTION_NAME);
            }
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