<?php
    namespace Core\Client\CloudMessaging;

    use Core\Event\CloudMessagingEvent;
    use Core\Event\Event;
    use Core\OpenLineage\OpenLineageEventManager;
    use Core\Service\Authentication\AuthenticationService;
    use Monolog\Logger;

    class FirebaseCloudMessagingClient implements CloudMessagingClient {

        private const FCM_SEND_URL_FORMAT = "https://fcm.googleapis.com/v1/projects/%s/messages:send";
        
        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = "fcm://%s";
        private const OPENLINEAGE_DATASET_NAME_FORMAT = "%s/%s";

        private readonly string $projectId;

        private ?AuthenticationService $authenticationService;

        private readonly \HttpClient $httpClient;

        private readonly Logger $logger;

        private ?OpenLineageEventManager $openLineageEventManager;

        public function __construct(string $projectId, \HttpClient $httpClient, Logger $logger) {
            $this->projectId = $projectId;
            $this->httpClient = $httpClient;
            $this->logger = $logger;
            $this->authenticationService = null;
            $this->openLineageEventManager = null;
        }

        public function setAuthenticationService(AuthenticationService $authenticationService) : void {
            $this->authenticationService = $authenticationService;
        }

        public function setOpenLineageEventManager(OpenLineageEventManager $openLineageEventManager) : void {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function publish(Event $event, array $deviceTokens) : void {
            foreach ($deviceTokens as &$deviceToken) {
                $payload = array(
                    "message" => array(
                        "token" => $deviceToken,
                        "android" => array(
                            "priority" => "high"
                        ),
                        "data" => array(
                            "event" => $event->getName(),
                            "args" => json_encode((object) $event->getArgs())
                        )
                    )
                );
                
                $namespace = sprintf(self::OPENLINEAGE_DATASET_NAMESPACE_FORMAT, $this->projectId);
                if ($event instanceof CloudMessagingEvent) {
                    foreach ($event->getSupportedDeviceTypes() as &$deviceType) {
                        $name = sprintf(self::OPENLINEAGE_DATASET_NAME_FORMAT, $deviceType->name, $event->getName());
                        $this->openLineageEventManager?->getCurrentEvent()?->addOutput($namespace, $name, $event->getArgs());                        
                    }
                }
                else {
                    $this->openLineageEventManager?->getCurrentEvent()?->addOutput($namespace, $event->getName(), $event->getArgs());   
                }
                
                $this->logger->debug("Publishing the '" . $event->getName() . "' event to FCM...", $payload);

                $this->httpClient->executeRequest(\HttpMethod::POST, sprintf(self::FCM_SEND_URL_FORMAT, $this->projectId),
                    array("Authorization: Bearer " . $this->authenticationService->getGoogleFcmAccessToken(), "Content-Type: application/json"), json_encode($payload));
            }
        }
    }
?>
