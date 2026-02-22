<?php
    namespace Core\Service\Monitoring;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class MonitoringServiceListener {
        
        private const RUN_DATA_CONSISTENCY_SCAN_ACTION_NAME = "RUN_DATA_CONSISTENCY_SCAN";
        private const RUN_DATA_CONSISTENCY_SCAN_ACTION_INTERVAL = CommonConstants::ONE_HOUR_SECONDS;

        private readonly MonitoringService $monitoringService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(MonitoringService $monitoringService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->monitoringService = $monitoringService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onDataConsistencyScanTriggered(mixed $message) : void {
            $this->monitoringService->fetchDataConsistencyIssues();
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::RUN_DATA_CONSISTENCY_SCAN_ACTION_NAME, self::RUN_DATA_CONSISTENCY_SCAN_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::DataConsistencyScanTriggered());
            }
        }
    }
?>