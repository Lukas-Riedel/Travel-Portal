<?php
    namespace Service\Service\Place;
    
    use Service\Service\Highlight\HighlightType;
    use Service\Service\Trip\TripService;

    class PlaceServiceListener {

        private const ONE_YEAR_SECONDS = 365 * 86400;

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
            $places = $this->placeService->getRegularPlaces(NULL, NULL, NULL, NULL, $message["albumId"], NULL, NULL,
                NULL, array(PlaceIncludedEntity::Dates->value, PlaceIncludedEntity::Categories->value), PlaceSortingStrategy::Default);
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

                $buckets = array();
                $encounteredAlbums = array();
                foreach ($this->placeService->getRegularPlace($place->getPlaceIdentifier()->getId())->getDates() as &$date) {
                    $album = $date->getAlbum();
                    if ($album !== NULL && !in_array($album->getId(), $encounteredAlbums)) {
                        $encounteredAlbums[] = $album->getId();
            
                        $tripId = $date->getTrip() === NULL
                            ? intval($date->getStart() / self::ONE_YEAR_SECONDS)
                            : $date->getTrip()->getId();
            
                        if (!isset($buckets[$tripId])) {
                            $buckets[$tripId] = 0;
                        }            
                        
                        $buckets[$tripId] += $album->getImagesCount() == 0 || ($album->getIndoorImagesCount() / $album->getImagesCount()) > 0.6
                            ? $album->getImagesCount() // This is an indoor-only location.
                            : $album->getImagesCount() - $album->getIndoorImagesCount(); // Exclude indoor photos from the score.
                    }                    
                }
        
                $this->placeService->updatePlaceScore($place->getPlaceIdentifier()->getId(), empty($buckets) ? 0 : max(array_values($buckets)));
            }
        }

        public function onCalendarInvalidated(mixed $message) : void {
            if ($message["calendar"] === \Calendar::Places->value) {
                $this->placeService->refreshCalendar($this->tripService);
                $this->tripService->updateAllDayTripsTripsDates();
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === \Calendar::Places->value) {
                $this->calendarClient->watchCalendar($message["calendar"]);
            }
        }

        public function onCategoryInvalidated(mixed $message) : void {
            $places = $this->placeService->getRegularPlaces($message["categoryId"], NULL, NULL, NULL, NULL, NULL, NULL, NULL, array(), PlaceSortingStrategy::Default);
            foreach ($places as &$place) {
                $this->eventPublisher->publishPlaceUpdatedEvent($place->getPlaceIdentifier()->getId());
            }
            
            $places = $this->placeService->getCandidatePlaces($message["categoryId"], NULL, NULL, array());
            foreach ($places as &$place) {
                $this->eventPublisher->publishPlaceUpdatedEvent($place->getPlaceIdentifier()->getId());
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

        public function onHighlightUpdated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Place->name) {
                $place = $this->placeService->getRegularPlace($message["entityId"]);
                if ($place !== NULL) {
                    $highlightQualities = [];

                    foreach ($place->getHighlights() as $highlight) {
                        $highlightQuality = $highlight->getQuality();

                        if ($highlightQuality === NULL) {
                            $this->placeService->updatePlaceQuality($place->getPlaceIdentifier()->getId(), NULL);
                            return;
                        }

                        $highlightQualities[] = $highlightQuality;
                    }

                    if (count($highlightQualities) === 0) {
                        $this->placeService->updatePlaceQuality($place->getPlaceIdentifier()->getId(), NULL);
                        return ;
                    }

                    $sumReciprocals = 0;
                    foreach ($highlightQualities as $q) {
                        $sumReciprocals += 1 / $q;
                    }
                    $this->placeService->updatePlaceQuality($place->getPlaceIdentifier()->getId(), count($highlightQualities) / $sumReciprocals);
                }
            }
        }
    }
?>