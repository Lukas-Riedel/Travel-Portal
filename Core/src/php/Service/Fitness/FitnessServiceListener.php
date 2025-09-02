<?php
    namespace Core\Service\Fitness;

    use Core\Common\CommonConstants;

    class FitnessServiceListener {

        private const FETCH_FITNESS_ACTION_NAME = "FETCH_FITNESS";
        private const FETCH_FITNESS_ACTION_INTERVAL = CommonConstants::FITNESS_RECORD_DURATION_SECONDS;

        // Only around 100 intervals fit into the size limit of 4kB for an FCM message.
        private const INTERVALS_LIMIT = 100;

        private readonly FitnessService $fitnessService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(FitnessService $fitnessService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->fitnessService = $fitnessService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onPlaceEventRemoved(mixed $message) : void {
            $this->fitnessService->removeStaleFitnessRecords();
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::FETCH_FITNESS_ACTION_NAME, self::FETCH_FITNESS_ACTION_INTERVAL)) {
                // Other intervals will eventually be fetched in the next scheduler trigger.
                $timestampsToUpdate = $this->fitnessService->getFitnessRecordTimestampsToUpdate(self::INTERVALS_LIMIT);

                if (count($timestampsToUpdate) > 0) {                
                    $intervals = array();
                    foreach ($timestampsToUpdate as &$timestampToUpdate) {
                        $intervals[] = array(
                            "start" => $timestampToUpdate,
                            "end" => $timestampToUpdate + CommonConstants::FITNESS_RECORD_DURATION_SECONDS
                        );
                    }
                    
                    $this->eventPublisher->publishFitnessActivityDetectedEvent($intervals);
                }
            }
        }
    }
?>