<?php
    namespace Service\Client;

    use Event;
    use Google\Auth\Credentials\ServiceAccountCredentials;
    use HttpMethod;
    use RuntimeException;

    class CloudMessagingClient {

        private const FCM_SEND_URL_FORMAT = "https://fcm.googleapis.com/v1/projects/%s/messages:send";

        private const SERVICE_ACCOUNT_PATH = __DIR__ . "/../config/firebase.json";

        private readonly string $projectId;

        public function __construct() {
            $this->projectId = json_decode(file_get_contents(self::SERVICE_ACCOUNT_PATH), TRUE)["project_id"];
        }

        public function publishEvent(Event $event, array $args, array $deviceTokens) : void {
            global $httpClient;

            $url = sprintf(self::FCM_SEND_URL_FORMAT, $this->projectId);
            $accessToken = $this->getAccessToken();

            foreach ($deviceTokens as &$deviceToken) {
                $payload = array(
                    "message" => array(
                        "token" => $deviceToken,
                        "data" => array(
                            "event" => $event->name,
                            "args" => json_encode($args)
                        )
                    )
                );

                $httpClient->executeRequest(HttpMethod::POST, $url, array("Authorization: Bearer " . $accessToken, "Content-Type: application/json"), json_encode($payload));
            }
        }

        private function getAccessToken() : string {
            $scopes = [
                "https://www.googleapis.com/auth/firebase.messaging",
                "https://www.googleapis.com/auth/cloud-platform",
            ];

            $response = (new ServiceAccountCredentials($scopes, self::SERVICE_ACCOUNT_PATH))->fetchAuthToken();

            if (!isset($response["access_token"])) {
                throw new RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }

            return $response["access_token"];
        }
    }
?>
