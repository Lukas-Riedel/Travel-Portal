<?php
    namespace Service\Service\Label;

use Service\Service\Place\PlaceService;
use Service\Service\Place\PlaceSortingStrategy;

    class LabelServiceListener {
        
        private const UPDATE_DYNAMIC_LABELS_ACTION_NAME = "UPDATE_DYNAMIC_LABELS";
        private const UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL = 43200;

        private readonly LabelService $labelService;

        private readonly PlaceService $placeService;

        private readonly \ConfigurationService $configurationService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(LabelService $labelService, PlaceService $placeService, \ConfigurationService $configurationService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->labelService = $labelService;
            $this->placeService = $placeService;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onAllDynamicLabelsInvalidated(mixed $message) : void {
            foreach ($this->configurationService->getConfigurationKeysForType("dynamicLabels") as &$labelName) {
                $this->labelService->removeLabelForAllPlaces($this->labelService->getOrCreateLabelId($labelName));
                $interval = $this->configurationService->getConfigurationForTypeAndKey("dynamicLabels", $labelName);
                $labeledPlaces = $this->placeService->getRegularPlaces(NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    time() - $interval, time(), array(), PlaceSortingStrategy::Default);
                
                foreach ($labeledPlaces as &$labeledPlace) {
                    $this->labelService->createLabel($labeledPlace->getId(), $labelName);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::UPDATE_DYNAMIC_LABELS_ACTION_NAME
                && time() - $message["lastTriggered"] > self::UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL) {
                $this->eventPublisher->publishAllDynamicLabelsInvalidatedEvent();                
                $this->scheduler->recordEventsTriggered(self::UPDATE_DYNAMIC_LABELS_ACTION_NAME);
            }
        }
    }
?>