<?php
    namespace Core\Service\Index;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Highlight\HighlightType;
    use Core\Service\Photo\PhotoService;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;

    class IndexServiceListener {
        
        private const UPDATE_INDEX_ACTION_NAME = "UPDATE_INDEX";
        private const UPDATE_INDEX_ACTION_INTERVAL = 6 * CommonConstants::ONE_HOUR_SECONDS;

        private readonly IndexService $indexService;
        private readonly PhotoService $photoService;
        private readonly HighlightService $highlightService;
        private readonly PlaceService $placeService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(IndexService $indexService, PhotoService $photoService, HighlightService $highlightService,
            PlaceService $placeService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->indexService = $indexService;
            $this->photoService = $photoService;
            $this->highlightService = $highlightService;
            $this->placeService = $placeService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onCategoryUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Category, $message["categoryId"]);
        }

        public function onFlightLogged(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airport, null);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airline, null);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip, null);
        }

        public function onFlightEventCreated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airport, null);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airline, null);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip, $message["tripId"]);
        }

        public function onFlightEventUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airport, null);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Airline, null);
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip, $message["tripId"]);
        }

        public function onPlaceUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Place, $message["placeId"]);
        }

        public function onPlaceEventCreated(mixed $message) : void {     
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Place, $message["placeId"]);
        }

        public function onPlaceEventUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Place, $message["placeId"]);
        }

        public function onStayEventCreated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip, $message["tripId"]);
        }

        public function onStayEventUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip, $message["tripId"]);
        }

        public function onTripUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip, $message["tripId"]);
        }

        public function onTripEventCreated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip, $message["tripId"]);
        }

        public function onTripEventUpdated(mixed $message) : void {
            $this->indexService->index(IndexType::Composite, IndexableEntityType::Trip, $message["tripId"]);
        }

        public function onAlbumUpdated(mixed $message) : void {
            // TODO: This is a bit weird, since the entity type is photo but an album identifier is passed to the function as the entity id.
            $this->indexService->index(IndexType::Photo, IndexableEntityType::Photo, $message["albumId"]);
        }

        public function onHighlightCreated(mixed $message) : void {
            $highlight = $this->highlightService->getHighlight($message["highlightId"]);
            if ($highlight === null) {
                return;
            }

            $album = $this->photoService->getAlbumForPhotoId($highlight->getPhoto()->getId());
            if ($album === null) {
                return;
            }

            // TODO: This is a bit weird, since the entity type is photo but an album identifier is passed to the function as the entity id.
            $this->indexService->index(IndexType::Photo, IndexableEntityType::Photo, $album->getId());
        }

        public function onHighlightUpdated(mixed $message) : void {
            $highlight = $this->highlightService->getHighlight($message["highlightId"]);
            if ($highlight === null) {
                return;
            }

            $album = $this->photoService->getAlbumForPhotoId($highlight->getPhoto()->getId());
            if ($album === null) {
                return;
            }

            // TODO: This is a bit weird, since the entity type is photo but an album identifier is passed to the function as the entity id.
            $this->indexService->index(IndexType::Photo, IndexableEntityType::Photo, $album->getId());
        }

        public function onHighlightRemoved(mixed $message) : void {
            $places = array();
            if ($message["highlightType"] === HighlightType::Trip->value) {
                $places = $this->placeService->getRegularPlaces(null, null, $message["entityId"], null, null, null, null, null,
                    null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
            }
            else if ($message["highlightType"] === HighlightType::Place->value) {
                $place = $this->placeService->getRegularPlace($message["entityId"]);
                if ($place !== null) {
                    $places = array($place);       
                }
            }
            else if ($message["highlightType"] === HighlightType::Category->value) {
                $places = $this->placeService->getRegularPlaces($message["entityId"], null, null, null, null, null, null, null,
                    null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
            }
            else if ($message["highlightType"] === HighlightType::Year->value) {
                $places = $this->placeService->getRegularPlaces(null, null, null, $message["entityId"], null, null, null, null,
                    null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
            }

            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $album = $date->getAlbum();
                    
                    if ($album !== null) {
                        // TODO: This is a bit weird, since the entity type is photo but an album identifier is passed to the function as the entity id.
                        $this->indexService->index(IndexType::Photo, IndexableEntityType::Photo, $album->getId());
                    }
                }
            }
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