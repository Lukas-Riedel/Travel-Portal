<?php
    namespace Core\OpenLineage;

    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Configuration\ConfigurationService;

    class OpenLineageEventManager {

        private ?OpenLineageEvent $event;

        private readonly array $openLineageEventPublishers;

        private readonly ConfigurationService $configurationService;

        private readonly EventPublisher $eventPublisher;

        private readonly string $coreBaseUrl;

        public function __construct(array $openLineageEventPublishers, ConfigurationService $configurationService,
            EventPublisher $eventPublisher, string $coreBaseUrl) {
            $this->event = null;
            $this->openLineageEventPublishers = $openLineageEventPublishers;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->coreBaseUrl = $coreBaseUrl;
        }

        public function initializeEvent(string $jobName) : void {
            $this->event = new OpenLineageEvent((new \DateTime("now", new \DateTimeZone("UTC")))->format("Y-m-d\TH:i:s\Z"), $jobName, array(), array(), $this->coreBaseUrl);
        }

        public function getCurrentEvent() : ?OpenLineageEvent {
            return $this->event;
        }

        public function publishCurrentEvent() : void {
            $this->publishEvent($this->event);
            $this->event = null;
        }

        public function publishCurrentEventAsync() : void {
            $this->publishEventAsync($this->event);
            $this->event = null;
        }

        public function publishEvent(OpenLineageEvent $event) : void {
            if ($event->shouldBePublished()) {
                foreach ($this->openLineageEventPublishers as $openLineageEventPublisher) {
                    $openLineageEventPublisher->publishEvent($event);
                }
            }           
        }

        public function publishEventAsync(OpenLineageEvent $event) : void {
            // Do not spam RMQ when all producers are disabled.
            $producers = $this->configurationService->getConfigurationEntry("openLineage")["producers"];
            $someProducerEnabled = array_reduce($producers, fn($carry, $producer) => $carry || $producer["enabled"], false);

            if ($someProducerEnabled && $event->shouldBePublished()) {                
                $this->eventPublisher->publish(Event::OpenLineageEventPublished($event));
            }
        }
    }
?>