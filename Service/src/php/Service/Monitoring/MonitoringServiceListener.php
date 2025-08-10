<?php
    namespace Service\Service\Monitoring;

    class MonitoringServiceListener {
        
        private const RUN_DATA_CONSISTENCY_SCAN_ACTION_NAME = "RUN_DATA_CONSISTENCY_SCAN";
        private const RUN_DATA_CONSISTENCY_SCAN_ACTION_INTERVAL = 3600;

        private readonly MonitoringService $monitoringService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(MonitoringService $monitoringService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->monitoringService = $monitoringService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onDataConsistencyScanTriggered(mixed $message) : void {
            $this->monitoringService->fetchDataConsistencyIssues();
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::RUN_DATA_CONSISTENCY_SCAN_ACTION_NAME, self::RUN_DATA_CONSISTENCY_SCAN_ACTION_INTERVAL)) {
                $this->eventPublisher->publishDataConsistencyScanTriggeredEvent();
            }
        }
    }
?>