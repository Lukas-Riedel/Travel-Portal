<?php
    namespace Core\Client\GenerativeContent;

    use Common\Client\Http\HttpClient;
    use Common\Client\Http\HttpMethod;
    use Core\Service\Authentication\AuthenticationService;
    use Core\Service\Configuration\ConfigurationService;
    use Monolog\Logger;

    class CortexGenerativeContentClient implements GenerativeContentClient {

        private const GENERATIVE_CONTENT_API_ENDPOINT_PATH = "/generativecontent";

        private const KEY_PLACEHOLDER_FORMAT = "{%s}";

        private const USER_ROLE = "user";

        private readonly AuthenticationService $authenticationService;
        private readonly ConfigurationService $configurationService;
        private readonly HttpClient $httpClient;
        private readonly Logger $logger;

        private readonly string $cortexHost;
        private readonly int $cortexPort;

        public function __construct(AuthenticationService $authenticationService, ConfigurationService $configurationService, HttpClient $httpClient,
            Logger $logger, string $cortexHost, int $cortexPort) {
            $this->authenticationService = $authenticationService;
            $this->configurationService = $configurationService;
            $this->httpClient = $httpClient;
            $this->logger = $logger;
            $this->cortexHost = $cortexHost;
            $this->cortexPort = $cortexPort;
        }

        public function getResponse(string $query, array $context, ?array $responseJsonSchema = null) : ?string {
            $response = $this->doGetResponse(array(array("role" => self::USER_ROLE, "text" => $this->createPrompt($query, $context))), null, null, $responseJsonSchema, null, null);
            if ($response === null) {
                return null;
            }

            return $responseJsonSchema !== null ? json_encode($response["response"]) : $response["response"];
        }

        public function getChatResponse(array $messages, ?string $accessToken, ?string $username, mixed $environment, array $skills) : ?GenerativeContentResult {
            $response = $this->doGetResponse($messages, $accessToken, $username, null, $environment, $skills);
            if ($response === null) {
                return null;
            }

            return new GenerativeContentResult($response["response"], $response["history"], $response["skills"], $response["topics"], $response["tokens"]);
        }

        // TODO: Keep only this method in GenerativeContentClient (rename to getResponse), and move others to GenerativeContentService. Prompt with placeholders would be created in GenerativeContentService, and all callers would call GenerativeContentService instead.
        private function doGetResponse(array $messages, ?string $accessToken, ?string $username, ?array $responseJsonSchema, mixed $environment, ?array $skills) : ?array {
            $payload = array("messages" => $messages, "environment" => $environment, "skills" => $skills);
            if ($username !== null) {
                $payload["context"] = $this->configurationService->getConfigurationEntry("agenticAi")["personalContext"][$username] ?? null;
            }
            if ($responseJsonSchema !== null) {
                $payload["schema"] = $responseJsonSchema;
            }

            $headers = array("Authorization: Bearer " . $this->authenticationService->getServiceAccessToken($accessToken), "Content-Type: application/json");
            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getCortexBaseUrl() . self::GENERATIVE_CONTENT_API_ENDPOINT_PATH, $headers, json_encode($payload));

            if (is_array($response) && isset($response["message"])) {
                $this->logger->error("The generative content request was not successful. Reason: " . $response["message"]);
                return null;
            }

            return $response;
        }

        private function createPrompt(string $query, array $context) : string {
            return str_replace(array_map(fn($key) => sprintf(self::KEY_PLACEHOLDER_FORMAT, $key), array_keys($context)), array_values($context), $query);
        }

        private function getCortexBaseUrl() : string {
            return "http://" . $this->cortexHost . ":" . $this->cortexPort;
        }
    }
?>
