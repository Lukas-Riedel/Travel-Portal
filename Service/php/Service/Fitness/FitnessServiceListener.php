<?php
    namespace Service\Service\Fitness;

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

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::FETCH_FITNESS_ACTION_NAME
                && time() - $message["lastTriggered"] > FitnessService::FITNESS_RECORD_DURATION) {
                $timestampsToUpdate = $this->fitnessService->getFitnessRecordTimestampsToUpdate();

                foreach ($timestampsToUpdate as &$timestampToUpdate) {
                    $this->eventPublisher->publishFitnessActivityDetectedEvent($timestampToUpdate,
                        $timestampToUpdate + FitnessService::FITNESS_RECORD_DURATION);
                }
                        
                $this->scheduler->recordEventsTriggered(self::FETCH_FITNESS_ACTION_NAME);
            }
        }
    }
?>