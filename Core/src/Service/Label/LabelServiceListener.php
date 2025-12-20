<?php
    namespace Core\Service\Label;

    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class LabelServiceListener {
        
        private const UPDATE_DYNAMIC_LABELS_ACTION_NAME = "UPDATE_DYNAMIC_LABELS";
        private const UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL = 6 * CommonConstants::ONE_HOUR_SECONDS;

        private readonly LabelService $labelService;

        private readonly PlaceService $placeService;

        private readonly ConfigurationService $configurationService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(LabelService $labelService, PlaceService $placeService, ConfigurationService $configurationService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->labelService = $labelService;
            $this->placeService = $placeService;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onAllDynamicLabelsInvalidated(mixed $message) : void {
            foreach ($this->configurationService->getConfigurationEntry("dynamicLabels") as &$dynamicLabel) {
                $this->labelService->removeLabelForAllPlaces($this->labelService->getOrCreateLabelId($dynamicLabel["name"]));
                $labeledPlaces = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null,
                    time() - $dynamicLabel["interval"], time(), null, null, array(), PlaceSortingStrategy::OldestAscending);
                
                foreach ($labeledPlaces as &$labeledPlace) {
                    $this->labelService->createLabel($labeledPlace->getId(), $dynamicLabel["name"]);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::UPDATE_DYNAMIC_LABELS_ACTION_NAME, self::UPDATE_DYNAMIC_LABELS_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::AllDynamicLabelsInvalidated());                
            }
        }
    }
?>