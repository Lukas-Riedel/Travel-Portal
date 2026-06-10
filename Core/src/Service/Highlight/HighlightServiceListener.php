<?php
    namespace Core\Service\Highlight;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Configuration\ConfigurationService;

    class HighlightServiceListener {
        
        private const FETCH_HIGHLIGHTS_ACTION_NAME = "FETCH_HIGHLIGHTS";
        private const FETCH_HIGHLIGHTS_ACTION_INTERVAL = 6 * CommonConstants::ONE_HOUR_SECONDS;

        private readonly HighlightService $highlightService;
        private readonly ConfigurationService $configurationService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(HighlightService $highlightService, ConfigurationService $configurationService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->highlightService = $highlightService;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onAllHighlightsInvalidated(mixed $message) : void {
            $this->highlightService->updateHighlights();
        }

        public function onHighlightRemoved(mixed $message) : void {
            $this->highlightService->deleteStaleHighlightIdentifiers();
            if ($this->highlightService->getHighlight($message["highlightId"]) === null) {
                $this->highlightService->deleteHighlightObject($message["highlightId"]);
            }
        }
        
        public function onPhotoInvalidated(mixed $message) : void {
            $this->highlightService->updateHighlightForPhoto($message["photoId"]);
        }

        public function onConfigurationEntryUpdated(mixed $message) : void {
            if ($message["key"] === "highlight") {
                foreach ($this->configurationService->getConfigurationEntry("highlight")["attribute"] as $attribute => $attributeConfiguration) {
                    foreach ($attributeConfiguration as &$newAttributeConfigurationEntry) {
                        $oldAttributeConfigurationEntry = current(array_filter($message["oldEntry"]["attribute"][$attribute] ?? array(),
                            fn($oldAttributeConfigurationEntry) => ($oldAttributeConfigurationEntry["id"] ?? null) === ($newAttributeConfigurationEntry["id"] ?? null)));

                        if (isset($oldAttributeConfigurationEntry["value"]) && $newAttributeConfigurationEntry["value"] !== $oldAttributeConfigurationEntry["value"]) {
                            switch ($attribute) {
                                case "composition":
                                    $this->highlightService->updateHighlightsComposition($oldAttributeConfigurationEntry["value"], $newAttributeConfigurationEntry["value"]);
                                    break;
                                case "sky":
                                    $this->highlightService->updateHighlightsSky($oldAttributeConfigurationEntry["value"], $newAttributeConfigurationEntry["value"]);
                                    break;
                                case "shadows":
                                    $this->highlightService->updateHighlightsShadows($oldAttributeConfigurationEntry["value"], $newAttributeConfigurationEntry["value"]);
                                    break;
                                case "circumstances":
                                    $this->highlightService->updateHighlightsCircumstances($oldAttributeConfigurationEntry["value"], $newAttributeConfigurationEntry["value"]);
                                    break;
                                case "atmosphere":
                                    $this->highlightService->updateHighlightsAtmosphere($oldAttributeConfigurationEntry["value"], $newAttributeConfigurationEntry["value"]);
                                    break;
                            }
                        }
                    }
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::FETCH_HIGHLIGHTS_ACTION_NAME, self::FETCH_HIGHLIGHTS_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::AllHighlightsInvalidated());            
            }
        }
    }
?>