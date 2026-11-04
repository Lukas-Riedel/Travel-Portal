<?php
    namespace Core\Service\Label;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Highlight\HighlightType;
    use Core\Service\Photo\PhotoService;
    use Core\Service\Place\Place;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;

    class LabelServiceListener {
        
        private const UPDATE_DYNAMIC_LABELS_ACTION_NAME = "UPDATE_DYNAMIC_LABELS";
        private const UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL = CommonConstants::ONE_HOUR_SECONDS;
        
        private readonly LabelService $labelService;
        private readonly PlaceService $placeService;
        private readonly PhotoService $photoService;
        private readonly HighlightService $highlightService;
        private readonly ConfigurationService $configurationService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        private readonly float $minPhotoSimilarity;
        private readonly float $minPhotosRatio;

        public function __construct(LabelService $labelService, PlaceService $placeService, PhotoService $photoService, HighlightService $highlightService,
            ConfigurationService $configurationService, EventPublisher $eventPublisher, Scheduler $scheduler, float $minPhotoSimilarity, float $minPhotosRatio) {
            $this->labelService = $labelService;
            $this->placeService = $placeService;
            $this->photoService = $photoService;
            $this->highlightService = $highlightService;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->minPhotoSimilarity = $minPhotoSimilarity;
            $this->minPhotosRatio = $minPhotosRatio;
        }

        public function onAllDynamicLabelsInvalidated(mixed $message) : void {
            foreach ($this->configurationService->getConfigurationEntry("dynamicLabels") as &$dynamicLabel) {
                $labelId = $this->labelService->getOrCreateLabelIdentifier($dynamicLabel["name"], null)->getId();

                $labeledPlaces = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null,
                    time() - $dynamicLabel["interval"], time(), null, null, array(), PlaceSortingStrategy::OldestAscending);
                
                $this->labelService->removeLabelForAllPlaces($labelId);
                foreach ($labeledPlaces as &$labeledPlace) {
                    $this->labelService->assignLabelToPlace($labeledPlace->getId(), $labelId);
                }
                
                $this->eventPublisher->publish(Event::LabelUpdated($labelId));
            }
        }

        public function onConfigurationEntryUpdated(mixed $message) : void {
            if ($message["key"] === "dynamicLabels") {
                $this->eventPublisher->publish(Event::AllDynamicLabelsInvalidated());
            }
        }

        public function onLabelInvalidated(mixed $message) : void {
            $this->reassignLabelToPlacesById($message["labelId"]);
        }

        public function onLabelUpdated(mixed $message) : void {
            $label = $this->labelService->getLabel($message["labelId"]);
            if ($label !== null && $label->getMainHighlight() === null && count($label->getHighlights()) > 0) {
                $this->labelService->updateLabelMainHighlight($message["labelId"], $label->getHighlights()[0]->getId());
            }
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Label->value) {
                $label = $this->labelService->getLabel($message["entityId"]);
                if ($label !== null) {
                    if ($label->getMainHighlight() === null) {
                        $this->labelService->updateLabelMainHighlight($message["entityId"], $message["highlightId"]);
                    }

                    $this->eventPublisher->publish(Event::LabelInvalidated($message["entityId"]));
                }
            }
        }

        public function onHighlightRemoved(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Label->value) {
                $label = $this->labelService->getLabel($message["entityId"]);
                if ($label !== null) {
                    if ($label->getMainHighlight() === null || $label->getMainHighlight()->getId() === $message["highlightId"]) {
                        if (count($label->getHighlights()) > 0) {
                            $this->labelService->updateLabelMainHighlight($label->getId(), $label->getHighlights()[0]->getId());
                        }
                        else {
                            $this->labelService->updateLabelMainHighlight($label->getId(), null);
                        }
                    }

                    $this->eventPublisher->publish(Event::LabelInvalidated($message["entityId"]));
                }
            }
        }

        public function onLabelCreated(mixed $message) : void {
            $this->reassignLabelToPlacesById($message["labelId"]);
        }

        public function onAlbumUpdated(mixed $message) : void {
            $profilesByLabelId = $this->getLabelProfiles($this->labelService->getAllLabels(array(LabelIncludedEntity::Highlights->value)));
            if (empty($profilesByLabelId)) {
                return;
            }

            // The place found for the album only contains the dates of that album, so it has to be fetched again to get all of its albums.
            $albumPlaces = $this->placeService->getRegularPlaces(null, null, null, null, $message["albumId"], null, null, null, null, null, null,  null, array(), PlaceSortingStrategy::OldestAscending);
            $changedLabelIds = array();
            foreach ($albumPlaces as &$albumPlace) {
                $place = $this->placeService->getRegularPlace($albumPlace->getId());
                if ($place !== null) {
                    $changedLabelIds = array_merge($changedLabelIds, $this->reassignLabelsToPlace($place, $profilesByLabelId));
                }
            }

            foreach (array_unique($changedLabelIds) as &$changedLabelId) {
                $this->eventPublisher->publish(Event::LabelUpdated($changedLabelId));
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::UPDATE_DYNAMIC_LABELS_ACTION_NAME, self::UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::AllDynamicLabelsInvalidated());
            }
        }

        private function reassignLabelToPlacesById(string $labelId) : void {
            $label = $this->labelService->getLabel($labelId);
            if ($label === null) {
                return;
            }

            $dynamicLabelNames = array_column($this->configurationService->getConfigurationEntry("dynamicLabels"), "name");
            if (!in_array($label->getName(), $dynamicLabelNames)) {
                $this->reassignLabelToPlaces($label);
            }

            $this->eventPublisher->publish(Event::LabelUpdated($labelId));
        }

        private function reassignLabelToPlaces(Label $label) : void {
            $profilesByLabelId = $this->getLabelProfiles(array($label));
            if (empty($profilesByLabelId)) {
                $this->labelService->removeLabelForAllPlaces($label->getId());
                return;
            }

            $places = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, null, null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
            foreach ($places as &$place) {
                $this->reassignLabelsToPlace($place, $profilesByLabelId);
            }
        }

        private function reassignLabelsToPlace(Place $place, array $profilesByLabelId) : array {
            $albumIds = array();
            foreach ($place->getDates() as &$date) {
                $album = $date->getAlbum();
                if ($album !== null) {
                    $albumIds[$album->getId()] = $album->getId();
                }
            }

            $changedLabelIds = array();
            foreach ($profilesByLabelId as $labelId => $profile) {
                $hasLabel = in_array($place->getId(), $profile["highlightPlaceIds"]);
                if (!$hasLabel) {
                    $counts = $this->photoService->getPhotosSimilarityCounts(array_values($albumIds), $profile["photoIds"], $this->minPhotoSimilarity);
                    $hasLabel = $counts->getTotal() > 0 && $counts->getMatching() / $counts->getTotal() >= $this->minPhotosRatio;
                }

                $wasChanged = $hasLabel ? $this->labelService->assignLabelToPlace($place->getId(), $labelId) : $this->labelService->removeLabelForPlace($place->getId(), $labelId);
                if ($wasChanged) {
                    $changedLabelIds[] = $labelId;
                }
            }

            return $changedLabelIds;
        }

        private function getLabelProfiles(array $labels) : array {
            $dynamicLabelNames = array_column($this->configurationService->getConfigurationEntry("dynamicLabels"), "name");

            $profilesByLabelId = array();
            foreach ($labels as &$label) {
                if (in_array($label->getName(), $dynamicLabelNames)) {
                    continue;
                }

                if (!empty($label->getHighlights())) {
                    $highlightIds = array_map(fn($highlight) => $highlight->getId(), $label->getHighlights());
                    $profilesByLabelId[$label->getId()] = array(
                        "photoIds" => array_map(fn($highlight) => $highlight->getPhoto()->getId(), $label->getHighlights()),
                        "highlightPlaceIds" => $this->highlightService->getPlaceIdsWithHighlights($highlightIds)
                    );
                }
            }

            return $profilesByLabelId;
        }
    }
?>