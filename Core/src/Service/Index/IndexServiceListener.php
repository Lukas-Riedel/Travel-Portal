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

        public function onCategoryUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Category);
        }

        public function onFlightLogged(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airport);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airline);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip);
        }

        public function onFlightEventCreated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airport);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airline);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip);
        }

        public function onFlightEventUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airport);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airline);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip);
        }

        public function onPlaceUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Place);
        }

        public function onPlaceEventCreated(mixed $message) : void {     
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Place);
        }

        public function onPlaceEventUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Place);
        }

        public function onStayEventCreated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip);
        }

        public function onStayEventUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip);
        }

        public function onTripUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip);
        }

        public function onTripEventCreated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip);
        }

        public function onTripEventUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip);
        }

        public function onIndexInvalidated(mixed $message) : void {
            $this->indexService->reindex();
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::UPDATE_INDEX_ACTION_NAME, self::UPDATE_INDEX_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::IndexInvalidated());
            }
        }
    }
?>