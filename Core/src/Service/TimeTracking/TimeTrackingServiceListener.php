<?php
    namespace Core\Service\TimeTracking;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class TimeTrackingServiceListener {
        
        private const BEGINNING_OF_YEAR_DATE_FORMAT = "1.1.%s 3:00 AM";

        private const RESET_OPENING_BALANCES_ACTION_NAME = "RESET_OPENING_BALANCES";
        
        private const CONSOLIDATE_TIME_TRACKING_EVENTS_ACTION_NAME = "CONSOLIDATE_TIME_TRACKING_EVENTS";
        private const CONSOLIDATE_TIME_TRACKING_EVENTS_INTERVAL = CommonConstants::ONE_DAY_SECONDS;

        private readonly TimeTrackingService $timeTrackingService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(TimeTrackingService $timeTrackingService, EventPublisher $eventPublisher, Scheduler $scheduler) {
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
            $beginningOfCurrentYearTimestamp = $this->getBeginningOfCurrentYear();
            $intervalSelector = fn($lastTriggered) => $lastTriggered < $beginningOfCurrentYearTimestamp ? 0 : PHP_INT_MAX;
            if ($this->scheduler->requestDynamicExecution(self::RESET_OPENING_BALANCES_ACTION_NAME, $intervalSelector)) {
                $this->eventPublisher->publish(Event::VacationReset());                  
            }

            if ($this->scheduler->requestExecution(self::CONSOLIDATE_TIME_TRACKING_EVENTS_ACTION_NAME, self::CONSOLIDATE_TIME_TRACKING_EVENTS_INTERVAL)) {
                $this->eventPublisher->publish(Event::TimeTrackingEventsAuditTriggered());                
            }
        }

        private function getBeginningOfCurrentYear() : int {
            return strtotime(sprintf(self::BEGINNING_OF_YEAR_DATE_FORMAT, date("Y")));
        }
    }
?>