<?php
    namespace Core\Event;

    use Core\Client\Cache\CacheClient;
    use Core\Client\CloudMessaging\CloudMessagingClient;
    use Core\Client\Messaging\MessagingClient;
    use Core\Service\Device\DeviceService;

    class EventPublisher {

        private const WEBHOOK_EVENT_CACHE_KEY_FORMAT = "EventPublisher:WebhookEvent:%s";

        private ?DeviceService $deviceService;
        
        private readonly MessagingClient $messagingClient;
        private readonly CloudMessagingClient $cloudMessagingClient;
        private readonly CacheClient $cacheClient;

        public function __construct(MessagingClient $messagingClient, CloudMessagingClient $cloudMessagingClient, CacheClient $cacheClient) {
            $this->messagingClient = $messagingClient;
            $this->cloudMessagingClient = $cloudMessagingClient;
            $this->cacheClient = $cacheClient;
        }

        public function setDeviceService(DeviceService $deviceService) : void {
            $this->deviceService = $deviceService;
        }

        public function publishStoredEvent(string $eventId) : ?string {
            $rawEvent = $this->cacheClient->get(sprintf(self::WEBHOOK_EVENT_CACHE_KEY_FORMAT, $eventId));
            if ($rawEvent === null) {
                throw new \RuntimeException("The stored event with the identifier '$eventId' does not exist.");
            }

            return $this->publishRawEvent($rawEvent["name"], $rawEvent["args"]);
        }

        public function publishRawEvent(string $name, mixed $args) : ?string {
            $method = new \ReflectionMethod(Event::class, $name);

            $orderedArgs = array();
            foreach ($method->getParameters() as $parameter) {
                $parameterName = $parameter->getName();
                if (!array_key_exists($parameterName, $args) && !$parameter->isDefaultValueAvailable()) {
                    throw new \RuntimeException("The required argument '$parameterName' is missing for the '$name' event.");
                }
                $orderedArgs[] = $args[$parameterName] ?? $parameter->getDefaultValue();
            }

            return $this->publish($method->invokeArgs(null, $orderedArgs));
        }

        // TODO: Go through all events and make sure it is fired meaningfuly (e.g., ForecastService shouldn't care about invalidating statistics)
        public function publish(Event $event) : ?string {
            if ($event instanceof WorkerEvent) {
                $this->messagingClient->publish(WORKER_QUEUE_NAME, $event, $event->getPriority());
                return null;
            }

            if ($event instanceof AgentEvent) {
                $device = $this->deviceService->getDevice($event->getAgentId());
                if ($device === null) {
                    throw new \InvalidArgumentException("The Agent with the identifier '" . $event->getAgentId() . "' does not exist.");
                }

                $data = $device->getData();
                if ($data === null || !isset($data["queueName"])) {
                    throw new \InvalidArgumentException("The Agent with the identifier '" . $event->getAgentId() . "' doesn't declare any queue.");
                }

                $this->messagingClient->publish($data["queueName"], $event);
                return null;
            }

            if ($event instanceof CloudMessagingEvent) {
                $deviceTokens = array();

                foreach ($event->getSupportedDeviceTypes() as &$deviceType) {
                    foreach ($event->getRequiredRoles() as &$requiredRole) {
                        $devices = $this->deviceService->getDevices($deviceType, $requiredRole);

                        foreach ($devices as &$device) {
                            $data = $device->getData();

                            if ($data !== null && isset($data["fcmToken"]) && !in_array($data["fcmToken"], $deviceTokens)) {
                                $deviceTokens[] = $data["fcmToken"];
                            }
                        }
                    }
                }

                $this->cloudMessagingClient->publish($event, $deviceTokens);
                return null;
            }

            if ($event instanceof WebhookEvent) {
                $eventId = uniqid("", true);
                $eventCacheKey = sprintf(self::WEBHOOK_EVENT_CACHE_KEY_FORMAT, $eventId);
                $this->cacheClient->set($eventCacheKey, $event, $event->getTtl());
                return $eventId;
            }

            if ($event instanceof CompositeEvent) {
                foreach ($event->getEvents() as $subEvent) {
                    $this->publish($subEvent);
                }
                return null;
            }

            throw new \RuntimeException("The event '" . $event->getName() . "' could not be published because it has an unsupported event type.");
        }
    }
?>