<?php
    namespace Service\Service\Highlight;

    class HighlightServiceListener {
        
        private const FETCH_HIGHLIGHTS_ACTION_NAME = "FETCH_HIGHLIGHTS";
        private const FETCH_HIGHLIGHTS_ACTION_INTERVAL = 21600;

        private readonly HighlightService $highlightService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(HighlightService $highlightService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->highlightService = $highlightService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onAllHighlightsInvalidated(mixed $message) : void {
            $this->highlightService->updateHighlights();
        }

        public function onHighlightRemovedChanged(mixed $message) : void {
            $this->highlightService->updateHighlights();
        }
        
        public function onPhotoInvalidated(mixed $message) : void {
            $this->highlightService->updateHighlightForPhoto($message["photoId"]);
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::FETCH_HIGHLIGHTS_ACTION_NAME
                && $message["timeSinceLastExecution"] > self::FETCH_HIGHLIGHTS_ACTION_INTERVAL) {
                $this->eventPublisher->publishAllHighlightsInvalidatedEvent();                
                $this->scheduler->recordEventsTriggered(self::FETCH_HIGHLIGHTS_ACTION_NAME);
            }
        }
    }
?>