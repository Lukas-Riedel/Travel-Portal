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

        public function __construct(LabelService $labelService, PlaceService $placeService, PhotoService $photoService, ConfigurationService $configurationService,
            EmbeddingClient $embeddingClient, EventPublisher $eventPublisher, Scheduler $scheduler, Logger $logger, int $maxHighlightsPerLabelCount) {
            $this->labelService = $labelService;
            $this->placeService = $placeService;
            $this->photoService = $photoService;
            $this->configurationService = $configurationService;
            $this->embeddingClient = $embeddingClient;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->logger = $logger;
            $this->maxHighlightsPerLabelCount = $maxHighlightsPerLabelCount;
        }

        public function onAllDynamicLabelsInvalidated(mixed $message) : void {
            foreach ($this->configurationService->getConfigurationEntry("dynamicLabels") as &$dynamicLabel) {
                $labelId = $this->labelService->getOrCreateLabelIdentifier($dynamicLabel["name"], null)->getId();

                $labeledPlaces = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null,
                    time() - $dynamicLabel["interval"], time(), null, null, array(PlaceIncludedEntity::Highlights->value), PlaceSortingStrategy::OldestAscending);
                
                $this->labelService->removeLabelForAllHighlights($labelId);
                foreach ($labeledPlaces as &$labeledPlace) {
                    foreach ($labeledPlace->getHighlights() as &$highlight) {
                        $this->labelService->reassignLabelToHighlight($highlight->getId(), $labelId, 1.0);
                    }
                }
                
                $this->eventPublisher->publish(Event::LabelUpdated($labelId));
            }
        }

        public function onConfigurationEntryUpdated(mixed $message) : void {
            if ($message["key"] === "dynamicLabels") {
                $this->eventPublisher->publish(Event::AllDynamicLabelsInvalidated());
            }
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

                $dynamicLabelNames = array_column($this->configurationService->getConfigurationEntry("dynamicLabels"), "name");
                if (!in_array($label->getName(), $dynamicLabelNames)) {
                    $this->reassignLabelToHighlights($label);
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
            
            if ($message["highlightType"] === HighlightType::Place->value) {
                $highlightEmbedding = $this->photoService->getPhotoEmbedding($message["photoId"])?->getEmbedding();
                if ($highlightEmbedding !== null) {
                    foreach ($this->labelService->getAllLabels(array()) as &$label) {
                        $keywordEmbeddings = $this->getKeywordEmbeddings($label);
                        if (empty($keywordEmbeddings)) {
                            continue;
                        }

                        $this->labelService->reassignLabelToHighlight($message["highlightId"], $label->getId(), $this->getLabelSimilarity($keywordEmbeddings, $highlightEmbedding));
                    }
                }
                
                $this->eventPublisher->publish(Event::AllDynamicLabelsInvalidated());
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
            $label = $this->labelService->getLabel($message["labelId"]);  
            if ($label !== null) {
                $dynamicLabelNames = array_column($this->configurationService->getConfigurationEntry("dynamicLabels"), "name");
                if (!in_array($label->getName(), $dynamicLabelNames)) {
                    $this->reassignLabelToHighlights($label);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::UPDATE_DYNAMIC_LABELS_ACTION_NAME, self::UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::AllDynamicLabelsInvalidated());
            }
        }

        private function reassignLabelToHighlights(Label $label) : void {
            $keywordEmbeddings = $this->getKeywordEmbeddings($label);
            if (!empty($keywordEmbeddings)) {
                $places = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, null, null, null,
                    null, array(PlaceIncludedEntity::Highlights->value), PlaceSortingStrategy::OldestAscending);
                
                foreach ($places as &$place) {
                    foreach ($place->getHighlights() as &$highlight) {
                        $highlightEmbedding = $this->photoService->getPhotoEmbedding($highlight->getPhoto()->getId())?->getEmbedding();
                        if ($highlightEmbedding === null) {
                            continue;
                        }

                        $this->labelService->reassignLabelToHighlight($highlight->getId(), $label->getId(), $this->getLabelSimilarity($keywordEmbeddings, $highlightEmbedding));
                    }
                }
            }
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

        private function getLabelSimilarity(array $keywordEmbeddings, array $highlightEmbedding) : float {
            return max(array_map(fn($keywordEmbedding) => $this->embeddingClient->getEmbeddingSimilarity($keywordEmbedding, $highlightEmbedding), $keywordEmbeddings));
        }
    }
?>