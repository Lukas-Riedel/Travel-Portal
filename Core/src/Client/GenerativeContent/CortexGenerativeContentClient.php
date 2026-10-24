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

    // TODO: Split some functionality (e.g., caching, context compactification) to GenerativeContentService.
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
        private readonly int $tokenCompactificationThreshold;

        public function __construct(AuthenticationService $authenticationService, CacheClient $distributedCacheClient, ConfigurationService $configurationService, HttpClient $httpClient,
            Logger $logger, string $cortexHost, int $cortexPort, int $tokenCompactificationThreshold) {
            $this->authenticationService = $authenticationService;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->configurationService = $configurationService;
            $this->httpClient = $httpClient;
            $this->logger = $logger;
            $this->cortexHost = $cortexHost;
            $this->cortexPort = $cortexPort;
            $this->tokenCompactificationThreshold = $tokenCompactificationThreshold;
        }

        public function getResponse(string $query, array $context, ?array $responseJsonSchema = null) : ?string {
            $response = $this->doGetResponse(array(array("role" => self::USER_ROLE, "text" => $this->createPrompt($query, $context))), null, null, $responseJsonSchema, null, array());
            if ($response === null) {
                return null;
            }

            return $responseJsonSchema !== null ? json_encode($response["response"]) : $response["response"];
        }

        public function getChatResponse(string $prompt, string $accessToken, ?string $username, ?string $conversationId, mixed $environment) : ?GenerativeContentResult {
            $targetConversationId = $conversationId ?? Uuid::uuid4()->toString();
            $cacheKey = sprintf(self::CONVERSATION_CACHE_KEY_FORMAT, $targetConversationId);

            $lastResponse = $this->distributedCacheClient->get($cacheKey) ?? array();

            $messages = $lastResponse["history"] ?? array();
            if ($lastResponse["tokens"] ?? 0 > $this->tokenCompactificationThreshold) {
                $messages = $this->compactMessages($messages);
            }

            $messages[] = array("role" => self::USER_ROLE, "text" => $prompt);

            $skills = $lastResponse["skills"] ?? $this->configurationService->getConfigurationEntry("agenticAi")["defaultSkills"];

            $response = $this->doGetResponse($messages, $accessToken, $username, null, $environment, $skills);
            if ($response === null) {
                return null;
            }

            $this->distributedCacheClient->set($cacheKey, $response, self::CONVERSATION_CACHE_TTL);

            return new GenerativeContentResult($response["response"], $targetConversationId, array());
        }

        public function getChatHistory(string $conversationId) : mixed {
            return $this->distributedCacheClient->get(sprintf(self::CONVERSATION_CACHE_KEY_FORMAT, $conversationId))["history"] ?? array();
        }

        private function doGetResponse(array $messages, ?string $accessToken, ?string $username, ?array $responseJsonSchema, mixed $environment, array $skills) : ?array {
            $payload = array("messages" => $this->sanitizeChatHistory($messages), "environment" => $environment, "skills" => $skills);
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

        private function compactMessages(array $messages) : array {
            $newMessages = array();

            foreach ($messages as &$message) {
                $newParts = array();
                foreach ($message["parts"] as &$part) {
                    if (isset($part["text"])) {
                        $newParts[] = $part;
                    }
                }

                if (!empty($newParts)) {
                    $newMessages[] = array(
                        "parts" => $newParts,
                        "role" => $message["role"]
                    );
                }
            }

            return $newMessages;
        }

        private function getCortexBaseUrl() : string {
            return "http://" . $this->cortexHost . ":" . $this->cortexPort;
        }

        // TODO: This exists only because empty objects are de/serialized as array, which leads to a type mismatch on the Python side. The correct solution is to ensure that empty objects are de/serialized correctly (e.g., stdClass).
        private function sanitizeChatHistory(array $messages): array {
            $messages = array_filter($messages, fn($message) => $message !== array());
            return array_values(array_map(fn($message) => $this->sanitizeChatMessage($message), $messages));
        }

        private function sanitizeChatMessage(mixed $data): mixed {
            if (is_array($data)) {
                if (isset($data["functionCall"]) && is_array($data["functionCall"])) {
                    if (array_key_exists("args", $data["functionCall"]) && empty($data["functionCall"]["args"])) {
                        $data["functionCall"]["args"] = new \stdClass();
                    }
                }
                return array_map(array($this, "sanitizeChatMessage"), $data);
            }
            return $data;
        }
    }
?>
