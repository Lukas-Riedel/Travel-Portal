<?php
    namespace Core\Event;

    use Common\Client\Cache\CacheClient;
    use Core\Client\CloudMessaging\CloudMessagingClient;
    use Core\Client\Messaging\MessagingClient;
    use Core\Common\CommonConstants;
    use Core\Service\Device\DeviceService;
    use Ramsey\Uuid\Uuid;

    class EventPublisher {

        private const WEBHOOK_EVENT_CACHE_KEY_FORMAT = "EventPublisher:WebhookEvent:%s";

        private ?DeviceService $deviceService;
        
        private readonly MessagingClient $messagingClient;
        private readonly CloudMessagingClient $cloudMessagingClient;
        private readonly CacheClient $distributedCacheClient;

        private readonly string $workerQueueName;
        private readonly string $cortexQueueName;

        public function __construct(MessagingClient $messagingClient, CloudMessagingClient $cloudMessagingClient,
            CacheClient $distributedCacheClient, string $workerQueueName, string $cortexQueueName) {
            $this->messagingClient = $messagingClient;
            $this->cloudMessagingClient = $cloudMessagingClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->workerQueueName = $workerQueueName;
            $this->cortexQueueName = $cortexQueueName;
        }

        public function setDeviceService(DeviceService $deviceService) : void {
            $this->deviceService = $deviceService;
        }

        public function publishStoredEvent(string $eventId) : ?string {
            $rawEvent = $this->distributedCacheClient->get(sprintf(self::WEBHOOK_EVENT_CACHE_KEY_FORMAT, $eventId));
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

        public function publish(Event $event, ?int $publishTimestamp = null) : ?string {
            if ($publishTimestamp !== null) {
                $this->distributedCacheClient->getSortedSet(CommonConstants::DELAYED_EVENTS_SORTED_SET_KEY)->add($event, $publishTimestamp);
                return null;
            }

            if ($event instanceof WorkerEvent) {
                $this->messagingClient->publish($this->workerQueueName, $event, $event->getPriority());
                return null;
            }

            if ($event instanceof CortexEvent) {
                $this->messagingClient->publish($this->cortexQueueName, $event, $event->getPriority());
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
                $eventId = Uuid::uuid4()->toString();
                $eventCacheKey = sprintf(self::WEBHOOK_EVENT_CACHE_KEY_FORMAT, $eventId);
                $this->distributedCacheClient->set($eventCacheKey, $event, $event->getTtl());
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