<?php
    namespace Core\Service\Place;

    use Core\Client\Calendar\Calendar;
    use Core\Common\CommonConstants;
    use Core\Service\Highlight\HighlightType;
    use Core\Service\Trip\TripService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Calendar\CalendarClient;

    class PlaceServiceListener {

        // TODO: Set the value.
        private const HIGHLIGHT_SCORE_MULTIPLIER = 0;
        private const PHOTO_SCORE_MULTIPLIER = 1;

        private const MAIN_HIGHLIGHT_QUALITY_MULTIPLIER = 3;

        private readonly PlaceService $placeService;

        private readonly TripService $tripService;

        private readonly CalendarClient $calendarClient;

        private readonly EventPublisher $eventPublisher;

        public function __construct(PlaceService $placeService, TripService $tripService,
            CalendarClient $calendarClient, EventPublisher $eventPublisher) {
            $this->placeService = $placeService;
            $this->tripService = $tripService;
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
        }
        
        public function onAlbumUpdated(mixed $message) : void {            
            $place = $this->placeService->getRegularPlaceForAlbum($message["albumId"]);
            if ($place !== null) {
                foreach ($place->getDates() as &$date) {
                    $trip = $date->getTrip();
                    if ($trip !== null) {
                        $this->eventPublisher->publish(Event::TripStatisticsInvalidated($trip->getId()));
                    }
                }

                foreach ($place->getCategories() as &$category) {
                    $this->eventPublisher->publish(Event::CategoryUpdated($category->getId()));
                }

                $this->updatePlaceScore($place->getPlaceIdentifier()->getId());
            }
        }

        public function onCalendarInvalidated(mixed $message) : void {
            if ($message["calendar"] === Calendar::Places->value) {
                $this->placeService->refreshCalendar($this->tripService);
                $this->tripService->updateAllDayTripsTripsDates();
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === Calendar::Places->value) {
                $this->calendarClient->watchCalendar(Calendar::Places);
            }
        }

        public function onCategoryInvalidated(mixed $message) : void {
            $places = $this->placeService->getRegularPlaces($message["categoryId"], null, null, null, null, null, null, null, null, null, array(), PlaceSortingStrategy::OldestAscending);
            foreach ($places as &$place) {
                $this->eventPublisher->publish(Event::PlaceUpdated($place->getPlaceIdentifier()->getId()));
            }
            
            $places = $this->placeService->getCandidatePlaces($message["categoryId"], null, null, array());
            foreach ($places as &$place) {
                $this->eventPublisher->publish(Event::PlaceUpdated($place->getPlaceIdentifier()->getId()));
            }
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Place->value) {
                $placeIdentifier = $this->placeService->getPlaceIdentifierById($message["entityId"]);
                if ($placeIdentifier !== null && $placeIdentifier->getMainHighlight() === null) {
                    $this->placeService->updatePlaceMainHighlight($message["entityId"], $message["highlightId"]);
                }
                $this->updatePlaceScore($message["entityId"]);
            }
        }

        public function onHighlightUpdated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Place->value) {
                $this->updatePlaceQuality($message["entityId"]);
            }
        }

        public function onHighlightRemoved(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Place->value) {
                $this->updatePlaceQuality($message["entityId"]);

                $place = $this->placeService->getRegularPlace($message["entityId"]);
                if ($place != null && $place->getMainHighlight() === null && count($place->getHighlights()) > 0) {
                    $this->placeService->updatePlaceMainHighlight($place->getId(), $place->getHighlights()[0]->getId());
                }
            }
        }

        public function onPlaceEventCreated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if (count($place->getDates()) > 0) {
                $firstDate = $place->getDates()[0];
                if ($firstDate->getStart() > time()) {
                    $this->placeService->getOrCreateCandidatePlace($place->getPlaceIdentifier());
                }
            }
        }

        public function onPlaceUpdated(mixed $message) : void {
            $this->updatePlaceQuality($message["placeId"]);
        }

        private function updatePlaceQuality(string $placeId) : void {
            $place = $this->placeService->getRegularPlace($placeId);

            if ($place !== null) {
                $highlightQualities = array();
                foreach ($place->getHighlights() as &$highlight) {
                    $highlightQuality = $highlight->getQuality();
                    if ($highlightQuality !== null) {
                        for ($i = 0; $i < ($highlight->getId() == $place->getMainHighlight()?->getId() ? self::MAIN_HIGHLIGHT_QUALITY_MULTIPLIER : 1); ++$i) {
                            $highlightQualities[] = $highlightQuality;
                        }
                    }
                }

                if (count($highlightQualities) === 0) {
                    $this->placeService->updatePlaceQuality($place->getPlaceIdentifier()->getId(), null);
                    return;
                }

                $product = array_reduce($highlightQualities, fn($carry, $q) => $carry * $q, 1.0);
                $this->placeService->updatePlaceQuality($place->getPlaceIdentifier()->getId(), pow($product, 1 / count($highlightQualities)));
            }
        }

        private function updatePlaceScore(string $placeId) : void {
            $place = $this->placeService->getRegularPlace($placeId);

            if ($place !== null) {
                $buckets = array();
                $encounteredAlbums = array();
                foreach ($place->getDates() as &$date) {
                    $album = $date->getAlbum();
                    if ($album !== null && !in_array($album->getId(), $encounteredAlbums)) {
                        $encounteredAlbums[] = $album->getId();
            
                        $tripId = $date->getTrip() === null
                            ? intval($date->getStart() / CommonConstants::ONE_YEAR_SECONDS)
                            : $date->getTrip()->getId();
            
                        $buckets[$tripId] ??= 0;
                        $buckets[$tripId] += $album->getImagesCount() == 0 || ($album->getIndoorImagesCount() / $album->getImagesCount()) > 0.6
                            ? $album->getImagesCount() // This is an indoor-only location.
                            : $album->getImagesCount() - $album->getIndoorImagesCount(); // Exclude indoor photos from the score.
                    }                    
                }

                $this->placeService->updatePlaceScore($placeId, self::PHOTO_SCORE_MULTIPLIER * (empty($buckets) ? 0 : max(array_values($buckets)))
                    + self::HIGHLIGHT_SCORE_MULTIPLIER * count($place->getHighlights()));
            }
        }
    }
?>