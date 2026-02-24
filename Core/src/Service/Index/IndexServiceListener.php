<?php
    namespace Core\Service\Index;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class IndexServiceListener {
        
        private const UPDATE_INDEX_ACTION_NAME = "UPDATE_INDEX";
        private const UPDATE_INDEX_ACTION_INTERVAL = CommonConstants::ONE_HOUR_SECONDS;

        private readonly IndexService $indexService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(IndexService $indexService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->indexService = $indexService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::UPDATE_INDEX_ACTION_NAME, self::UPDATE_INDEX_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::IndexInvalidated());
            }
        }

        public function onIndexInvalidated(mixed $message) : void {
            $this->indexService->index();
        }
    }
?>