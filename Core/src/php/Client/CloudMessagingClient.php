<?php
    namespace Core\Client;

    use Core\Event\CloudMessagingEvent;
    use Core\Event\Event;
    use Core\OpenLineage\OpenLineageEventManager;
    use Google\Auth\Credentials\ServiceAccountCredentials;
    use Monolog\Logger;

    class CloudMessagingClient {

        private const FCM_SEND_URL_FORMAT = "https://fcm.googleapis.com/v1/projects/%s/messages:send";
        
        private const OPENLINEAGE_DATASET_NAMESPACE_FORMAT = "fcm://%s";
        private const OPENLINEAGE_DATASET_NAME_FORMAT = "%s/%s";

        // TODO: Create a constant for all fields in JSON.
        private const FIREBASE_CONFIGURATION_FILE_PATH = __DIR__ . "/../../../config/firebase.json";

        private readonly string $projectId;

        private readonly Logger $logger;
        private ?OpenLineageEventManager $openLineageEventManager;

        public function __construct(Logger $logger) {
            $this->projectId = json_decode(file_get_contents(self::FIREBASE_CONFIGURATION_FILE_PATH), true)["project_id"];
            $this->logger = $logger;
            $this->openLineageEventManager = null;
        }

        public function setOpenLineageEventManager(OpenLineageEventManager $openLineageEventManager) : void {
            $this->openLineageEventManager = $openLineageEventManager;
        }

        public function publish(Event $event, array $deviceTokens) : void {
            global $httpClient;

            $url = sprintf(self::FCM_SEND_URL_FORMAT, $this->projectId);
            $accessToken = $this->getAccessToken();

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

                $httpClient->executeRequest(\HttpMethod::POST, $url, array("Authorization: Bearer " . $accessToken, "Content-Type: application/json"), json_encode($payload));
            }
        }

        // TODO: Move to AuthenticationService.
        private function getAccessToken() : string {
            $scopes = array(
                "https://www.googleapis.com/auth/firebase.messaging",
                "https://www.googleapis.com/auth/cloud-platform",
            );

            // TODO: Cache the token.
            $response = (new ServiceAccountCredentials($scopes, self::FIREBASE_CONFIGURATION_FILE_PATH))->fetchAuthToken();

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }

            return $response["access_token"];
        }
    }
?>
