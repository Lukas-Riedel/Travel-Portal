<?php
    namespace Service\Service\TimeTracking;

    class TimeTrackingServiceListener {
        
        private const BEGINNING_OF_YEAR = "1.1.";

        private const RESET_OPENING_BALANCES_ACTION_NAME = "RESET_OPENING_BALANCES";
        
        private const CONSOLIDATE_TIME_TRACKING_EVENTS_ACTION_NAME = "CONSOLIDATE_TIME_TRACKING_EVENTS";
        private const CONSOLIDATE_TIME_TRACKING_EVENTS_INTERVAL = 86400;

        private readonly TimeTrackingService $timeTrackingService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(TimeTrackingService $timeTrackingService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->timeTrackingService = $timeTrackingService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onVacationReset(mixed $message) : void {
            $this->timeTrackingService->resetOpeningBalances($this->getBeginningOfCurrentYear());
        }

        public function onTimeTrackingEventsAuditTriggered(mixed $message) : void {
            $this->timeTrackingService->executeTimeTrackingEventsAudit();
        }

        public function onSchedulerTriggered(mixed $message) : void {
            foreach ($message["actions"] as &$action) {
                if ($action["name"] === self::RESET_OPENING_BALANCES_ACTION_NAME) {
                    $beginningOfCurrentYearTimestamp = strtotime($this->getBeginningOfCurrentYear());

                    if ($action["lastTriggered"] < $beginningOfCurrentYearTimestamp) {
                        $this->eventPublisher->publishVacationResetEvent();                        
                        $this->scheduler->recordEventsTriggered(self::RESET_OPENING_BALANCES_ACTION_NAME);
                    }
                }

                if ($action["name"] === self::CONSOLIDATE_TIME_TRACKING_EVENTS_ACTION_NAME
                    && time() - $action["lastTriggered"] > self::CONSOLIDATE_TIME_TRACKING_EVENTS_INTERVAL) {
                    $this->eventPublisher->publishTimeTrackingEventsAuditTriggered();                        
                    $this->scheduler->recordEventsTriggered(self::CONSOLIDATE_TIME_TRACKING_EVENTS_ACTION_NAME);
                }
            }
        }

        private function getBeginningOfCurrentYear() : string {
            return self::BEGINNING_OF_YEAR . date("Y");
        }
    }
?>