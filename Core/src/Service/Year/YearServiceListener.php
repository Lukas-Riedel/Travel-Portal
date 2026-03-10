<?php
    namespace Core\Service\Year;

    use Core\Common\CommonConstants;
    use Core\Service\Highlight\HighlightType;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Monolog\Logger;

    class YearServiceListener {
        
        private const UPDATE_YEAR_STATISTICS_ACTION_NAME = "UPDATE_YEAR_STATISTICS";
        private const UPDATE_YEAR_STATISTICS_ACTION_INTERVAL = 21 * CommonConstants::ONE_DAY_SECONDS;

        private readonly YearService $yearService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        private readonly Logger $logger;

        private readonly int $maxHighlightsPerYearCount;

        public function __construct(YearService $yearService, EventPublisher $eventPublisher, Scheduler $scheduler, Logger $logger, int $maxHighlightsPerYearCount) {
            $this->yearService = $yearService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->logger = $logger;
            $this->maxHighlightsPerYearCount = $maxHighlightsPerYearCount;
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Year->value) {
                $year = $this->yearService->getYear($message["entityId"]);
                if ($year !== null) {
                    if ($year->getMainHighlight() === null) {
                        $this->yearService->updateYearMainHighlight($message["entityId"], $message["highlightId"]);
                    }

                    if (count($year->getHighlights()) !== $this->maxHighlightsPerYearCount) {
                        $this->yearService->refreshYearHighlights($message["entityId"], $this->maxHighlightsPerYearCount);
                    }
                }                
            }
        }

        public function onHighlightRemoved(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Year->value) {
                $year = $this->yearService->getYear($message["entityId"]);
                if ($year != null) {
                    if ($year->getMainHighlight() === null || $year->getMainHighlight()->getId() === $message["highlightId"]) {
                        if (count($year->getHighlights()) > 0) {
                            $this->yearService->updateYearMainHighlight($year->getId(), $year->getHighlights()[0]->getId());
                        } 
                        else {
                            $this->yearService->updateYearMainHighlight($year->getId(), null);
                        }
                    }
                
                    if (count($year->getHighlights()) !== $this->maxHighlightsPerYearCount) {
                        $this->logger->debug("There are " . count($year->getHighlights()) . "/" . $this->maxHighlightsPerYearCount . " highlights for the '" . $message["entityId"] . "' category. Refreshing the highlights...");
                        $this->yearService->refreshYearHighlights($message["entityId"], $this->maxHighlightsPerYearCount);
                    }
                }
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