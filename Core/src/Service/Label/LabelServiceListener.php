<?php
    namespace Core\Service\Label;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Highlight\HighlightType;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Monolog\Logger;

    class LabelServiceListener {
        
        private const UPDATE_DYNAMIC_LABELS_ACTION_NAME = "UPDATE_DYNAMIC_LABELS";
        private const UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL = CommonConstants::ONE_HOUR_SECONDS;
        
        private readonly LabelService $labelService;
        private readonly PlaceService $placeService;
        private readonly ConfigurationService $configurationService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        private readonly Logger $logger;

        private readonly int $maxHighlightsPerLabelCount;

        public function __construct(LabelService $labelService, PlaceService $placeService, ConfigurationService $configurationService, EventPublisher $eventPublisher,
            Scheduler $scheduler, Logger $logger, int $maxHighlightsPerLabelCount) {
            $this->labelService = $labelService;
            $this->placeService = $placeService;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->logger = $logger;
            $this->maxHighlightsPerLabelCount = $maxHighlightsPerLabelCount;
        }

        public function onAllDynamicLabelsInvalidated(mixed $message) : void {
            foreach ($this->configurationService->getConfigurationEntry("dynamicLabels") as &$dynamicLabel) {
                $labelId = $this->labelService->getOrCreateLabelId($dynamicLabel["name"]);

                $labeledPlaces = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null,
                    time() - $dynamicLabel["interval"], time(), null, null, array(), PlaceSortingStrategy::OldestAscending);

                $this->labelService->reassignLabelForPlaces($labelId, array_map(fn($place) => $place->getId(), $labeledPlaces));
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

        public function onPlaceCreated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            if ($place === null) {
                $place = $this->placeService->getCandidatePlace($message["placeId"]);
            }

            if ($place !== null) {
                $this->labelService->assignLabelsToPlace($place);
            }
        }

        public function onLabelCreated(mixed $message) : void {
            $dynamicLabelNames = array_column($this->configurationService->getConfigurationEntry("dynamicLabels"), "name");
            $label = $this->labelService->getLabel($message["labelId"]);            
            if ($label !== null && !in_array($label->getName(), $dynamicLabelNames)) {
                $this->labelService->assignPlacesToLabel($label);
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::UPDATE_DYNAMIC_LABELS_ACTION_NAME, self::UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::AllDynamicLabelsInvalidated());
            }
        }
    }
?>