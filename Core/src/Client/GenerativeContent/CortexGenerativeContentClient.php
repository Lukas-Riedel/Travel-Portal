<?php
    namespace Core\Client\GenerativeContent;

    use Common\Client\Cache\CacheClient;
    use Common\Client\Http\HttpClient;
    use Common\Client\Http\HttpMethod;
    use Core\Common\CommonConstants;
    use Core\Service\Authentication\AuthenticationService;
    use Core\Service\Configuration\ConfigurationService;
    use Monolog\Logger;
    use Ramsey\Uuid\Uuid;

    class CortexGenerativeContentClient implements GenerativeContentClient {

        private const GENERATIVE_CONTENT_API_ENDPOINT_PATH = "/generativecontent";

        private const KEY_PLACEHOLDER_FORMAT = "{%s}";

        private const USER_ROLE = "user";

        private const CONVERSATION_CACHE_KEY_FORMAT = "CortexGenerativeContentClient:Conversation:%s";
        private const CONVERSATION_CACHE_TTL = CommonConstants::ONE_DAY_SECONDS;

        private readonly AuthenticationService $authenticationService;
        private readonly CacheClient $distributedCacheClient;
        private readonly ConfigurationService $configurationService;
        private readonly HttpClient $httpClient;
        private readonly Logger $logger;

        private readonly string $cortexHost;
        private readonly int $cortexPort;

        public function __construct(AuthenticationService $authenticationService, CacheClient $distributedCacheClient, ConfigurationService $configurationService, HttpClient $httpClient, Logger $logger, string $cortexHost, int $cortexPort) {
            $this->authenticationService = $authenticationService;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->configurationService = $configurationService;
            $this->httpClient = $httpClient;
            $this->logger = $logger;
            $this->cortexHost = $cortexHost;
            $this->cortexPort = $cortexPort;
        }

        public function getResponse(string $query, array $context, ?array $responseJsonSchema = null) : ?string {
            $response = $this->doGetResponse(array(array("role" => self::USER_ROLE, "text" => $this->createPrompt($query, $context))), null, null, $responseJsonSchema, false, null);
            if ($response === null) {
                return null;
            }

            return $responseJsonSchema !== null ? json_encode($response["text"]) : $response["text"];
        }

        public function getChatResponse(string $prompt, string $accessToken, ?string $username = null, ?string $conversationId = null, mixed $environment = null) : ?GenerativeContentResult {
            $targetConversationId = $conversationId ?? Uuid::uuid4()->toString();
            $cacheKey = sprintf(self::CONVERSATION_CACHE_KEY_FORMAT, $targetConversationId);

            $messages = $this->distributedCacheClient->get($cacheKey) ?? array();
            $messages[] = array("role" => self::USER_ROLE, "text" => $prompt);

            $response = $this->doGetResponse($messages, $accessToken, $username, null, true, $environment);
            if ($response === null) {
                return null;
            }

            if (isset($response["history"])) {
                $this->distributedCacheClient->set($cacheKey, $response["history"], self::CONVERSATION_CACHE_TTL);
            }

            return new GenerativeContentResult($response["text"], $targetConversationId, array());
        }

        public function getChatHistory(string $conversationId) : mixed {
            return $this->distributedCacheClient->get(sprintf(self::CONVERSATION_CACHE_KEY_FORMAT, $conversationId)) ?? array();
        }

        private function doGetResponse(array $messages, ?string $accessToken, ?string $username, ?array $responseJsonSchema, bool $useSkills, mixed $environment) : ?array {
            $payload = array("messages" => $this->sanitizeChatHistory($messages), "environment" => $environment);
            if ($responseJsonSchema !== null) {
                $payload["schema"] = $responseJsonSchema;
            }
            if ($username !== null) {
                $payload["context"] = $this->configurationService->getConfigurationEntry("agenticAi")["personalContext"][$username] ?? null;
            }

            $headers = array("Authorization: Bearer " . $this->authenticationService->getServiceAccessToken($accessToken), "Content-Type: application/json");
            $url = sprintf("%s%s?useSkills=%s", $this->getCortexBaseUrl(), self::GENERATIVE_CONTENT_API_ENDPOINT_PATH, $useSkills ? "true" : "false");
            $response = $this->httpClient->executeRequest(HttpMethod::POST, $url, $headers, json_encode($payload));

            if (is_array($response) && isset($response["message"])) {
                $this->logger->error("The generative content request was not successful. Reason: " . $response["message"]);
                return null;
            }

            $this->logger->debug("The generative content request was successful. Prompt: '" . $messages[count($messages) - 1]["text"] . "' Response: '" . is_array($response["text"]) ? json_encode($response["text"]) : $response["text"] . "'");
            return $response;
        }

        private function createPrompt(string $query, array $context) : string {
            return str_replace(array_map(fn($key) => sprintf(self::KEY_PLACEHOLDER_FORMAT, $key), array_keys($context)), array_values($context), $query);
        }

        private function getCortexBaseUrl() : string {
            return "http://" . $this->cortexHost . ":" . $this->cortexPort;
        }

        // TODO: This exists only because empty objects are de/serialized as array, which leads to a type mismatch on the Python side. The correct solution is to ensure that empty objects are de/serialized correctly (e.g., stdClass).
        private function sanitizeChatHistory(mixed $data): mixed {
            if (is_array($data)) {
                if (isset($data["functionCall"]) && is_array($data["functionCall"])) {
                    if (array_key_exists("args", $data["functionCall"]) && empty($data["functionCall"]["args"])) {
                        $data["functionCall"]["args"] = new \stdClass();
                    }
                }
                return array_map(array($this, "sanitizeChatHistory"), $data);
            }
            return $data;
        }
    }
?>
