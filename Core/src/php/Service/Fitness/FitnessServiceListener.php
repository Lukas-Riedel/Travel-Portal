<?php
    namespace Core\Service\Fitness;

    use Core\Common\CommonConstants;

    class FitnessServiceListener {

        private const FETCH_FITNESS_ACTION_NAME = "FETCH_FITNESS";

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
            if ($this->scheduler->requestExecution(self::FETCH_FITNESS_ACTION_NAME, CommonConstants::FITNESS_RECORD_DURATION_SECONDS)) {
                $timestampsToUpdate = $this->fitnessService->getFitnessRecordTimestampsToUpdate();

                foreach ($timestampsToUpdate as &$timestampToUpdate) {
                    $this->eventPublisher->publishFitnessActivityDetectedEvent($timestampToUpdate,
                        $timestampToUpdate + CommonConstants::FITNESS_RECORD_DURATION_SECONDS);
                }
            }
        }
    }
?>