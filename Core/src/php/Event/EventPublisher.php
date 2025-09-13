<?php
    namespace Core\Event;

    use Core\Client\CloudMessagingClient;
    use Core\Client\MessagingClient;
    use Core\Service\Device\DeviceService;

    class EventPublisher {

        private ?DeviceService $deviceService;
        
        private readonly MessagingClient $messagingClient;
        private readonly CloudMessagingClient $cloudMessagingClient;

        public function __construct(MessagingClient $messagingClient, CloudMessagingClient $cloudMessagingClient) {
            $this->messagingClient = $messagingClient;
            $this->cloudMessagingClient = $cloudMessagingClient;
        }

        public function setDeviceService(DeviceService $deviceService) : void {
            $this->deviceService = $deviceService;
        }

        // TODO: Go through all events and make sure it is fired meaningfuly (e.g., ForecastService shouldn't care about invalidating statistics)
        public function publish(Event $event) : void {
            if ($event instanceof WorkerEvent) {
                $this->messagingClient->publish(WORKER_QUEUE_NAME, $event, $event->getPriority());
            }
            else if ($event instanceof AgentEvent) {
                $this->messagingClient->publish(AGENT_QUEUE_NAME, $event);
            }
            else if ($event instanceof CloudMessagingEvent) {
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
            }
            else if ($event instanceof CompositeEvent) {
                foreach ($event->getEvents() as $subEvent) {
                    $this->publish($subEvent);
                }
            }
            else {
                throw new \RuntimeException("The event '" . $event->getName() . "' could not be published because it has an unsupported event type.");
            }
        }
    }
?>