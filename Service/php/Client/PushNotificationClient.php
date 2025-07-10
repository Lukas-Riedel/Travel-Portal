<?php
    namespace Service\Client;

    use Event;
    use Google\Auth\Credentials\ServiceAccountCredentials;
    use HttpMethod;
    use RuntimeException;

    class PushNotificationClient {

        private const FCM_SEND_URL_FORMAT = "https://fcm.googleapis.com/v1/projects/%s/messages:send";

        private const SERVICE_ACCOUNT_PATH = __DIR__ . "/../config/firebase.json";

        private readonly string $projectId;

        public function __construct() {
            $this->projectId = json_decode(file_get_contents(self::SERVICE_ACCOUNT_PATH), TRUE)["project_id"];
        }

        public function publishEvent(Event $event, array $args) : void {
            global $httpClient;

            $url = sprintf(self::FCM_SEND_URL_FORMAT, $this->projectId);
            $payload = array(
                "message" => array(
                    "topic" => "allClients",
                    "data" => array(
                        "event" => $event->name,
                        "args" => json_encode($args)
                    )
                )
            );

            $httpClient->executeRequest(HttpMethod::POST, $url, array("Authorization: Bearer " . $this->fetchAccessToken(), "Content-Type: application/json"), json_encode($payload));
        }

        private function fetchAccessToken() : string {
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
