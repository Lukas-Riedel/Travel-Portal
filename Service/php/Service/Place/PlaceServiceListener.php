<?php
    namespace Service\Service\Place;
    
    use Service\Service\Highlight\HighlightType;
    use Service\Service\Trip\TripService;

    class PlaceServiceListener {

        private readonly PlaceService $placeService;

        private readonly TripService $tripService;

        private readonly \CalendarClient $calendarClient;

        private readonly \EventPublisher $eventPublisher;

        public function __construct(PlaceService $placeService, TripService $tripService,
            \CalendarClient $calendarClient, \EventPublisher $eventPublisher) {
            $this->placeService = $placeService;
            $this->tripService = $tripService;
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
        }
        
        public function onAlbumUpdated(mixed $message) : void {            
            $places = $this->placeService->getRegularPlaces(NULL, NULL, NULL, NULL, $message["albumId"], NULL,
                NULL, array(PlaceIncludedEntity::Dates->value, PlaceIncludedEntity::Categories->value));
            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $trip = $date->getTrip();
                    if ($trip !== NULL) {
                        $this->eventPublisher->publishTripStatisticsInvalidatedEvent($trip->getId());
                    }
                }

                foreach ($place->getCategories() as &$category) {
                    $this->eventPublisher->publishCategoryUpdatedEvent($category->getId());
                }
            }
        }

        public function onCalendarInvalidated(mixed $message) : void {
            if ($message["calendar"] === \Calendar::Places->value) {
                $this->placeService->refreshCalendar($this->tripService);
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === \Calendar::Places->value) {
                $this->calendarClient->watchCalendar($message["calendar"]);
            }
        }

        public function onCategoryInvalidated(mixed $message) : void {
            $places = $this->placeService->getRegularPlaces($message["categoryId"], NULL, NULL, NULL, NULL, NULL, NULL, array());
            foreach ($places as &$place) {
                $this->eventPublisher->publishPlaceUpdatedEvent($place->getPlaceIdentifier());
            }
            
            $places = $this->placeService->getCandidatePlaces($message["categoryId"], NULL, array());
            foreach ($places as &$place) {
                $this->eventPublisher->publishPlaceUpdatedEvent($place->getPlaceIdentifier());
            }
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Place->name) {
                $placeIdentifier = $this->placeService->getPlaceIdentifierById($message["entityId"]);
                if ($placeIdentifier !== NULL && $placeIdentifier->getMainHighlight() === NULL) {
                    $this->placeService->updatePlaceMainHighlight($message["entityId"], $message["highlightId"]);
                }
            }
        }
    }
?>