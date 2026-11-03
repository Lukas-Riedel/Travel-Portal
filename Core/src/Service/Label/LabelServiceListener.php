<?php
    namespace Core\Service\Label;

    use Core\Client\Embedding\EmbeddingClient;
    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Highlight\HighlightType;
    use Core\Service\Photo\PhotoService;
    use Core\Service\Place\Place;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Monolog\Logger;

    class LabelServiceListener {
        
        private const UPDATE_DYNAMIC_LABELS_ACTION_NAME = "UPDATE_DYNAMIC_LABELS";
        private const UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL = CommonConstants::ONE_HOUR_SECONDS;
        
        private readonly LabelService $labelService;
        private readonly PlaceService $placeService;
        private readonly PhotoService $photoService;
        private readonly ConfigurationService $configurationService;
        private readonly EmbeddingClient $embeddingClient;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        private readonly Logger $logger;

        private readonly int $maxHighlightsPerLabelCount;
        private readonly float $minPhotoSimilarity;
        private readonly float $minPhotosRatio;

        public function __construct(LabelService $labelService, PlaceService $placeService, PhotoService $photoService, ConfigurationService $configurationService,
            EmbeddingClient $embeddingClient, EventPublisher $eventPublisher, Scheduler $scheduler, Logger $logger, int $maxHighlightsPerLabelCount,
            float $minPhotoSimilarity, float $minPhotosRatio) {
            $this->labelService = $labelService;
            $this->placeService = $placeService;
            $this->photoService = $photoService;
            $this->configurationService = $configurationService;
            $this->embeddingClient = $embeddingClient;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->logger = $logger;
            $this->maxHighlightsPerLabelCount = $maxHighlightsPerLabelCount;
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
            if ($label !== null) {
                if ($label->getMainHighlight() === null && count($label->getHighlights()) > 0) {
                    $this->labelService->updateLabelMainHighlight($message["labelId"], $label->getHighlights()[0]->getId());
                }

                if (count($label->getHighlights()) !== $this->maxHighlightsPerLabelCount) {
                    $this->labelService->refreshLabelHighlights($message["labelId"], $this->maxHighlightsPerLabelCount);
                }
            }
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Label->value) {
                $label = $this->labelService->getLabel($message["entityId"]);
                if ($label !== null) {
                    if ($label->getMainHighlight() === null) {
                        $this->labelService->updateLabelMainHighlight($message["entityId"], $message["highlightId"]);
                    }
                    
                    if (count($label->getHighlights()) !== $this->maxHighlightsPerLabelCount) {
                        $this->labelService->refreshLabelHighlights($message["entityId"], $this->maxHighlightsPerLabelCount);
                    }
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
                    
                    if (count($label->getHighlights()) !== $this->maxHighlightsPerLabelCount) {
                        $this->logger->debug("There are " . count($label->getHighlights()) . "/" . $this->maxHighlightsPerLabelCount . " highlights for the '" . $message["entityId"] . "' label. Refreshing the highlights...");
                        $this->labelService->refreshLabelHighlights($message["entityId"], $this->maxHighlightsPerLabelCount);
                    }
                }
            }
        }

        public function onLabelCreated(mixed $message) : void {
            $this->reassignLabelToPlacesById($message["labelId"]);
        }

        public function onAlbumUpdated(mixed $message) : void {
            $keywordEmbeddingsByLabelId = $this->getKeywordEmbeddingsByLabelId($this->labelService->getAllLabels(array()));
            if (empty($keywordEmbeddingsByLabelId)) {
                return;
            }

            // The place found for the album only contains the dates of that album, so it has to be fetched again to get all of its albums.
            $albumPlaces = $this->placeService->getRegularPlaces(null, null, null, null, $message["albumId"], null, null, null, null, null, null,  null, array(), PlaceSortingStrategy::OldestAscending);
            $changedLabelIds = array();
            foreach ($albumPlaces as &$albumPlace) {
                $place = $this->placeService->getRegularPlace($albumPlace->getId());
                if ($place !== null) {
                    $changedLabelIds = array_merge($changedLabelIds, $this->reassignLabelsToPlace($place, $keywordEmbeddingsByLabelId));
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
            $keywordEmbeddingsByLabelId = $this->getKeywordEmbeddingsByLabelId(array($label));
            if (empty($keywordEmbeddingsByLabelId)) {
                $this->labelService->removeLabelForAllPlaces($label->getId());
                return;
            }

            $places = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, null, null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
            foreach ($places as &$place) {
                $this->reassignLabelsToPlace($place, $keywordEmbeddingsByLabelId);
            }
        }

        private function reassignLabelsToPlace(Place $place, array $keywordEmbeddingsByLabelId) : array {
            $matchingPhotosCounts = array_fill_keys(array_keys($keywordEmbeddingsByLabelId), 0);
            $photosCount = 0;

            $processedAlbumIds = array();
            foreach ($place->getDates() as &$date) {
                $album = $date->getAlbum();
                if ($album === null || isset($processedAlbumIds[$album->getId()])) {
                    continue;
                }
                $processedAlbumIds[$album->getId()] = true;

                foreach ($this->photoService->getPhotoEmbeddings($album->getId()) as &$photoEmbedding) {
                    $photosCount++;
                    foreach ($keywordEmbeddingsByLabelId as $labelId => $keywordEmbeddings) {
                        if ($this->getLabelSimilarity($keywordEmbeddings, $photoEmbedding->getEmbedding()) > $this->minPhotoSimilarity) {
                            $matchingPhotosCounts[$labelId]++;
                        }
                    }
                }
            }

            $changedLabelIds = array();
            foreach ($matchingPhotosCounts as $labelId => $matchingPhotosCount) {
                if ($photosCount > 0 && $matchingPhotosCount / $photosCount >= $this->minPhotosRatio) {
                    $wasChanged = $this->labelService->assignLabelToPlace($place->getId(), $labelId);
                }
                else {
                    $wasChanged = $this->labelService->removeLabelForPlace($place->getId(), $labelId);
                }

                if ($wasChanged) {
                    $changedLabelIds[] = $labelId;
                }
            }

            return $changedLabelIds;
        }

        private function getKeywordEmbeddingsByLabelId(array $labels) : array {
            $dynamicLabelNames = array_column($this->configurationService->getConfigurationEntry("dynamicLabels"), "name");

            $keywordEmbeddingsByLabelId = array();
            foreach ($labels as &$label) {
                if (in_array($label->getName(), $dynamicLabelNames)) {
                    continue;
                }

                $keywordEmbeddings = $this->getKeywordEmbeddings($label);
                if (!empty($keywordEmbeddings)) {
                    $keywordEmbeddingsByLabelId[$label->getId()] = $keywordEmbeddings;
                }
            }

            return $keywordEmbeddingsByLabelId;
        }

        private function getKeywordEmbeddings(Label $label) : array {
            $keywordEmbeddings = array();
            foreach ($label->getMetadata()?->getKeywords() ?? array() as &$keyword) {
                // TODO: Do not harcode the language here. The value depends on language that the label keywords are written in.
                $keywordEmbedding = $this->embeddingClient->getTextEmbedding($keyword, "cs");
                if ($keywordEmbedding !== null) {
                    $keywordEmbeddings[] = $keywordEmbedding;
                }
            }

            return $keywordEmbeddings;
        }

        private function getLabelSimilarity(array $keywordEmbeddings, array $photoEmbedding) : float {
            return max(array_map(fn($keywordEmbedding) => $this->embeddingClient->getEmbeddingSimilarity($keywordEmbedding, $photoEmbedding), $keywordEmbeddings));
        }
    }
?>