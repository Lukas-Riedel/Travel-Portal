<?php
    namespace Core\Service\Place;

    use Core\Client\Calendar\Calendar;
    use Core\Common\CommonConstants;
    use Core\Service\Highlight\HighlightType;
    use Core\Service\Trip\TripService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Calendar\CalendarClient;
    use Core\Service\Category\CategoryCategory;
    use Core\Service\Category\CategoryService;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceSortingStrategy;

    class PlaceServiceListener {

        private readonly PlaceService $placeService;

        private readonly TripService $tripService;
        private readonly CategoryService $categoryService;

        private readonly CalendarClient $calendarClient;

        private readonly EventPublisher $eventPublisher;

        private readonly int $minHighlightsPerPlaceCount;
        private readonly int $maxHighlightsPerPlaceCount;
        private readonly int $highlightScoreMultiplier;
        private readonly int $photoScoreMultiplier;
        private readonly int $mainHighlightQualityMultiplier;

        public function __construct(PlaceService $placeService, TripService $tripService, CategoryService $categoryService,
            CalendarClient $calendarClient, EventPublisher $eventPublisher, int $minHighlightsPerPlaceCount, int $maxHighlightsPerPlaceCount,
            int $highlightScoreMultiplier, int $photoScoreMultiplier, int $mainHighlightQualityMultiplier) {
            $this->placeService = $placeService;
            $this->tripService = $tripService;
            $this->categoryService = $categoryService;
            $this->calendarClient = $calendarClient;
            $this->eventPublisher = $eventPublisher;
            $this->minHighlightsPerPlaceCount = $minHighlightsPerPlaceCount;
            $this->maxHighlightsPerPlaceCount = $maxHighlightsPerPlaceCount;
            $this->highlightScoreMultiplier = $highlightScoreMultiplier;
            $this->photoScoreMultiplier = $photoScoreMultiplier;
            $this->mainHighlightQualityMultiplier = $mainHighlightQualityMultiplier;
        }

        public function onCategoryRenamed(mixed $message) : void {
            $categoryIdentifier = $this->categoryService->getCategoryIdentifierById($message["categoryId"]);
            if ($categoryIdentifier?->getCategory() === CategoryCategory::Country) {
                $places = $this->placeService->getRegularPlaces($message["categoryId"], null, null, null, null, null, null,
                    null, null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
                    
                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {
                        $this->placeService->refreshPlaceEventLocation($place->getId(), $date->getStart());
                    }
                }
            }
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
            }
        }

        public function onCalendarWatchRenewing(mixed $message) : void {
            if ($message["calendar"] === Calendar::Places->value) {
                $this->calendarClient->watchCalendar(Calendar::Places);
            }
        }

        public function onCategoryInvalidated(mixed $message) : void {
            $places = $this->placeService->getRegularPlaces($message["categoryId"], null, null, null, null, null, null,
                null, null, null, null, array(), PlaceSortingStrategy::OldestAscending);
            foreach ($places as &$place) {
                $this->eventPublisher->publish(Event::PlaceUpdated($place->getPlaceIdentifier()->getId()));
            }
            
            $places = $this->placeService->getCandidatePlaces($message["categoryId"], null, null, null, array());
            foreach ($places as &$place) {
                $this->eventPublisher->publish(Event::PlaceUpdated($place->getPlaceIdentifier()->getId()));
            }
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Place->value) {
                $placeIdentifier = $this->placeService->getPlaceIdentifierById($message["entityId"]);
                if ($placeIdentifier !== null && $placeIdentifier->getMainHighlight() === null) {
                    $this->placeService->updatePlaceMainHighlight($message["entityId"], $message["highlightId"]);
                    $this->eventPublisher->publish(Event::HighlightsSelectingTriggered(HighlightType::Place->value, $message["entityId"], $placeIdentifier->getName(),
                        $this->getSuggestedHighlightsCount($message["entityId"]), false, true));
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

                foreach ($place->getCategories() as &$category) {
                    // TODO: At this point, the highlight is already removed and we don't know if it was also in the category or not.
                    $this->eventPublisher->publish(Event::HighlightRemoved(HighlightType::Category->value, $category->getId(), $message["highlightId"]));
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

        public function onSchedulerTriggered(mixed $message) : void {
            global $scheduler, $distributedCacheClient;

            if ($scheduler->requestExecution("TMP_SELECT_HIGHLIGHTS", CommonConstants::ONE_DAY_SECONDS)) {
                $places = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, time(), null, null, array(PlaceIncludedEntity::Highlights->value, PlaceIncludedEntity::Categories->value), PlaceSortingStrategy::OldestDescending);
                $ids = array();
                foreach ($places as &$place) {
                    if (count($place->getHighlights()) !== 1) {
                        continue;
                    }
                    
                    if ($distributedCacheClient->get("HighlightsSelectingTriggeredHandler:ContentQuery:placeHighlightsSelecting:" . $place->getId()) === null && !in_array($place->getId(), $ids)) {
                        $ids[] = $place->getId();
                    }

                    foreach ($place->getCategories() as &$category) {
                        if ($distributedCacheClient->get("HighlightsSelectingTriggeredHandler:ContentQuery:categoryHighlightsSelecting:" . $category->getId()) === null && !in_array($category->getId(), $ids)) {
                            $ids[] = $category->getId();
                        }
                    }
                    
                    if (count($ids) > 40) {
                        break;
                    }

                    $this->eventPublisher->publish(Event::HighlightsSelectingTriggered(HighlightType::Place->value, $place->getId(), $place->getName(), $this->getSuggestedHighlightsCount($place->getId()), false, false));
                }
            }
        }

        private function getSuggestedHighlightsCount(string $placeId) : int {
            $v = max(0.0, min(1.0, $this->placeService->getPlaceSignificance($placeId) / 100.0));
            $range = $this->maxHighlightsPerPlaceCount - $this->minHighlightsPerPlaceCount;
            return (int) round($this->minHighlightsPerPlaceCount + $range * pow($v, 1.4));
        }

        private function updatePlaceQuality(string $placeId) : void {
            $place = $this->placeService->getRegularPlace($placeId);

            if ($place !== null) {
                $highlightQualities = array();
                foreach ($place->getHighlights() as &$highlight) {
                    $highlightQuality = $highlight->getQuality();
                    if ($highlightQuality !== null) {
                        $count = ($highlight->getId() === $place->getMainHighlight()?->getId()) ? $this->mainHighlightQualityMultiplier : 1;
                        $highlightQualities = array_merge($highlightQualities, array_fill(0, $count, $highlightQuality));
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

                $this->placeService->updatePlaceScore($placeId, $this->photoScoreMultiplier * (empty($buckets) ? 0 : max(array_values($buckets)))
                    + $this->highlightScoreMultiplier * count($place->getHighlights()));
            }
        }
    }
?>
