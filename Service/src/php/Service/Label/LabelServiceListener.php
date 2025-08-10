<?php
    namespace Service\Service\Label;

    use Service\Service\Configuration\ConfigurationService;
    use Service\Service\Place\PlaceService;
    use Service\Service\Place\PlaceSortingStrategy;

    class LabelServiceListener {
        
        private const UPDATE_DYNAMIC_LABELS_ACTION_NAME = "UPDATE_DYNAMIC_LABELS";
        private const UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL = 43200;

        private readonly LabelService $labelService;

        private readonly PlaceService $placeService;

        private readonly ConfigurationService $configurationService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(LabelService $labelService, PlaceService $placeService, ConfigurationService $configurationService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->labelService = $labelService;
            $this->placeService = $placeService;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onAllDynamicLabelsInvalidated(mixed $message) : void {
            foreach ($this->configurationService->getConfigurationEntry("dynamicLabels") as &$dynamicLabel) {
                $this->labelService->removeLabelForAllPlaces($this->labelService->getOrCreateLabelId($dynamicLabel["name"]));
                $labeledPlaces = $this->placeService->getRegularPlaces(NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                    time() - $dynamicLabel["interval"], time(), array(), PlaceSortingStrategy::Default);
                
                foreach ($labeledPlaces as &$labeledPlace) {
                    $this->labelService->createLabel($labeledPlace->getId(), $dynamicLabel["name"]);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::UPDATE_DYNAMIC_LABELS_ACTION_NAME, self::UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL)) {
                $this->eventPublisher->publishAllDynamicLabelsInvalidatedEvent();                
            }
        }
    }
?>