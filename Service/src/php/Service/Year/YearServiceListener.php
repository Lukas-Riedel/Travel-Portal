<?php
    namespace Service\Service\Year;
    
    use Service\Service\Highlight\HighlightType;

    class YearServiceListener {
        
        private const UPDATE_YEAR_STATISTICS_ACTION_NAME = "UPDATE_YEAR_STATISTICS";
        private const UPDATE_YEAR_STATISTICS_ACTION_INTERVAL = 86400 * 14;

        private readonly YearService $yearService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(YearService $yearService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->yearService = $yearService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Year->name) {
                $yearIdentifier = $this->yearService->getYearIdentifier($message["entityId"]);
                if ($yearIdentifier !== NULL && $yearIdentifier->getMainHighlight() === NULL) {
                    $this->yearService->updateYearMainHighlight($message["entityId"], $message["highlightId"]);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {            
            if ($message["action"] === self::UPDATE_YEAR_STATISTICS_ACTION_NAME
                && time() - $message["lastTriggered"] > self::UPDATE_YEAR_STATISTICS_ACTION_INTERVAL) {
                $years = $this->yearService->getYears(array());
                foreach ($years as &$year) {
                    $this->eventPublisher->publishYearStatisticsInvalidatedEvent($year->getId());
                }                        
                $this->scheduler->recordEventsTriggered(self::UPDATE_YEAR_STATISTICS_ACTION_NAME);
            }
        }
    }
?>