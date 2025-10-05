<?php
    namespace Core\OpenLineage;

    use Core\Event\Event;
    use Core\Event\EventPublisher;

    class OpenLineageEventManager {

        private ?OpenLineageEvent $event;

        private readonly array $openLineageEventPublishers;

        private readonly EventPublisher $eventPublisher;

        public function __construct(array $openLineageEventPublishers, EventPublisher $eventPublisher) {
            $this->event = null;
            $this->openLineageEventPublishers = $openLineageEventPublishers;
            $this->eventPublisher = $eventPublisher;
        }

        public function initializeEvent(string $jobName) : void {
            $this->event = new OpenLineageEvent((new \DateTime("now", new \DateTimeZone("UTC")))->format("Y-m-d\TH:i:s\Z"), $jobName, array(), array());
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