<?php
    namespace Core\Service\Year;

    use Core\Common\CommonConstants;
    use Core\Service\Highlight\HighlightType;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class YearServiceListener {
        
        private const UPDATE_YEAR_STATISTICS_ACTION_NAME = "UPDATE_YEAR_STATISTICS";
        private const UPDATE_YEAR_STATISTICS_ACTION_INTERVAL = 21 * CommonConstants::ONE_DAY_SECONDS;
        
        private const MAX_HIGHLIGHTS_PER_YEAR_COUNT = 30;

        private readonly YearService $yearService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(YearService $yearService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->yearService = $yearService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Year->value) {
                $yearIdentifier = $this->yearService->getYearIdentifier($message["entityId"]);
                if ($yearIdentifier !== null && $yearIdentifier->getMainHighlight() === null) {
                    $this->yearService->updateYearMainHighlight($message["entityId"], $message["highlightId"]);
                }
                    
                $this->eventPublisher->publish(Event::HighlightsSelectingTriggered(HighlightType::Year->value, $message["entityId"], $message["entityId"],
                    self::MAX_HIGHLIGHTS_PER_YEAR_COUNT, true));
            }
        }

        public function onHighlightRemoved(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Year->value) {
                $year = $this->yearService->getYear($message["entityId"]);
                if ($year != null && ($year->getMainHighlight() === null || $year->getMainHighlight()->getId() === $message["highlightId"])) {
                    if (count($year->getHighlights()) > 0) {
                        $this->yearService->updateYearMainHighlight($year->getId(), $year->getHighlights()[0]->getId());
                    } 
                    else {
                        $this->yearService->updateYearMainHighlight($year->getId(), null);
                    }
                }
                
                $this->eventPublisher->publish(Event::HighlightsSelectingTriggered(HighlightType::Year->value, $message["entityId"], $message["entityId"],
                    self::MAX_HIGHLIGHTS_PER_YEAR_COUNT, true));
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {  
            if ($this->scheduler->requestExecution(self::UPDATE_YEAR_STATISTICS_ACTION_NAME, self::UPDATE_YEAR_STATISTICS_ACTION_INTERVAL)) {                
                $years = $this->yearService->getYears(array());
                
                foreach ($years as &$year) {
                    $this->eventPublisher->publish(Event::YearStatisticsInvalidated($year->getId()));
                }                        
            }
        }
    }
?>