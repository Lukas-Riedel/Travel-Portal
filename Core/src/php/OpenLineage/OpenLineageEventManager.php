<?php
    namespace Core\OpenLineage;

    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Service\Authentication\AuthenticationService;

    class OpenLineageEventManager {

        private const IBM_CREATE_OPENLINEAGE_EVENT_URL = "https://api.dataplatform.dev.cloud.ibm.com/gov_lineage/v2/lineage_events/openlineage";

        private ?OpenLineageEvent $event;

        private readonly AuthenticationService $authenticationService;

        private readonly \HttpClient $httpClient;

        private readonly EventPublisher $eventPublisher;

        public function __construct(AuthenticationService $authenticationService, \HttpClient $httpClient, EventPublisher $eventPublisher) {
            $this->event = null;
            $this->authenticationService = $authenticationService;
            $this->httpClient = $httpClient;
            $this->eventPublisher = $eventPublisher;
        }

        public function initializeEvent(string $jobName) : void {
            $this->event = new OpenLineageEvent($jobName, array(), array());
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
                $this->httpClient->executeRequest(\HttpMethod::POST, self::IBM_CREATE_OPENLINEAGE_EVENT_URL, array("Content-Type: application/json", 
                    "Authorization: Bearer " . $this->authenticationService->getIbmCloudAccessToken()), json_encode($event)); 
            }           
        }

        public function publishEventAsync(OpenLineageEvent $event) : void {
            if ($event->shouldBePublished()) {                
                $this->eventPublisher->publish(Event::OpenLineageEventPublished($event));
            }
        }
    }
?>