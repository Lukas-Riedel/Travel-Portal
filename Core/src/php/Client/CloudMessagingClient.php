<?php
    namespace Core\Client;

    use Core\Event\Event;
    use Google\Auth\Credentials\ServiceAccountCredentials;
    use HttpMethod;
    use Monolog\Logger;
    use RuntimeException;

    class CloudMessagingClient {

        private const FCM_SEND_URL_FORMAT = "https://fcm.googleapis.com/v1/projects/%s/messages:send";

        private const FIREBASE_CONFIGURATION_FILE_PATH = __DIR__ . "/../../../config/firebase.json";

        private readonly string $projectId;

        private readonly Logger $logger;

        public function __construct(Logger $logger) {
            $this->projectId = json_decode(file_get_contents(self::FIREBASE_CONFIGURATION_FILE_PATH), true)["project_id"];
            $this->logger = $logger;
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
                
                $this->logger->debug("Publishing the '" . $event->getName() . "' event to FCM...", $payload);
                $httpClient->executeRequest(HttpMethod::POST, $url, array("Authorization: Bearer " . $accessToken, "Content-Type: application/json"), json_encode($payload));
            }
        }

        private function getAccessToken() : string {
            $scopes = array(
                "https://www.googleapis.com/auth/firebase.messaging",
                "https://www.googleapis.com/auth/cloud-platform",
            );

            // TODO: Cache the token.
            $response = (new ServiceAccountCredentials($scopes, self::FIREBASE_CONFIGURATION_FILE_PATH))->fetchAuthToken();

            if (!isset($response["access_token"])) {
                throw new RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }

            return $response["access_token"];
        }
    }
?>
