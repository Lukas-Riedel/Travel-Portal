<?php
    namespace Core\OpenLineage;

    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Configuration\ConfigurationService;

    class OpenLineageEventManager {

        private readonly EventPublisher $eventPublisher;
        private readonly array $openLineageEventPublishers;

        private readonly string $coreBaseUrl;
        
        private ?OpenLineageEvent $event;

        public function __construct(array $openLineageEventPublishers, EventPublisher $eventPublisher, string $coreBaseUrl) {
            $this->event = null;
            $this->openLineageEventPublishers = $openLineageEventPublishers;
            $this->eventPublisher = $eventPublisher;
            $this->coreBaseUrl = $coreBaseUrl;
        }

        public static function isOpenLineageEnabled(ConfigurationService $configurationService) : bool {
            $producers = $configurationService->getConfigurationEntry("openLineage")["producer"];
            return array_reduce($producers, fn($carry, $producer) => $carry || $producer["enabled"], false);            
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
            if ($event->shouldBePublished()) {                
                $this->eventPublisher->publish(Event::OpenLineageEventPublished($event));
            }
        }
    }
?>