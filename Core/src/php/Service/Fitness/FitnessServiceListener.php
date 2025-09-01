<?php
    namespace Core\Service\Fitness;

    use Core\Common\CommonConstants;

    class FitnessServiceListener {

        private const FETCH_FITNESS_ACTION_NAME = "FETCH_FITNESS";
        private const FETCH_FITNESS_ACTION_INTERVAL = CommonConstants::FITNESS_RECORD_DURATION_SECONDS;

        private const INTERVALS_BATCH_MAX_SIZE = 50;

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
                $timestampsToUpdate = $this->fitnessService->getFitnessRecordTimestampsToUpdate();

                $intervals = array();
                foreach ($timestampsToUpdate as &$timestampToUpdate) {
                    $intervals[] = array(
                        "start" => $timestampToUpdate,
                        "end" => $timestampToUpdate + CommonConstants::FITNESS_RECORD_DURATION_SECONDS
                    );

                    if (count($intervals) == self::INTERVALS_BATCH_MAX_SIZE) {
                        $this->eventPublisher->publishFitnessActivityDetectedEvent($intervals);
                        $intervals = array();
                    }
                }
                
                if (count($intervals) > 0) {
                    $this->eventPublisher->publishFitnessActivityDetectedEvent($intervals);
                }
            }
        }
    }
?>