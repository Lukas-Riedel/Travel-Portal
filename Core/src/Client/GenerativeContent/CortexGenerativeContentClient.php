<?php
    namespace Core\Client\GenerativeContent;

    use Common\Client\Cache\CacheClient;
    use Common\Client\Http\HttpClient;
    use Common\Client\Http\HttpMethod;
    use Core\Common\CommonConstants;
    use Core\Service\Authentication\AuthenticationService;
    use Monolog\Logger;
    use Ramsey\Uuid\Uuid;

    class CortexGenerativeContentClient implements GenerativeContentClient {

        private const GENERATIVE_CONTENT_API_ENDPOINT_PATH = "/generativecontent";

        private const KEY_PLACEHOLDER_FORMAT = "{%s}";

        private const CONVERSATION_CACHE_KEY_FORMAT = "CortexGenerativeContentClient:Conversation:%s";
        private const CONVERSATION_CACHE_TTL = CommonConstants::ONE_DAY_SECONDS;

        private readonly AuthenticationService $authenticationService;
        private readonly CacheClient $distributedCacheClient;
        private readonly HttpClient $httpClient;
        private readonly Logger $logger;

        private readonly string $cortexHost;
        private readonly int $cortexPort;

        public function __construct(AuthenticationService $authenticationService, CacheClient $distributedCacheClient, HttpClient $httpClient, Logger $logger, string $cortexHost, int $cortexPort) {
            $this->authenticationService = $authenticationService;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->httpClient = $httpClient;
            $this->logger = $logger;
            $this->cortexHost = $cortexHost;
            $this->cortexPort = $cortexPort;
        }

        public function getResponse(string $query, array $context, ?array $responseJsonSchema = null) : ?string {
            return $this->doGetResponse(array(array("role" => "user", "text" => $this->createPrompt($query, $context))), $responseJsonSchema);
        }

        public function getChatResponse(string $prompt, ?string $conversationId = null, ?array $responseJsonSchema = null) : ?GenerativeContentResult {
            $targetConversationId = $conversationId ?? Uuid::uuid4()->toString();
            $cacheKey = sprintf(self::CONVERSATION_CACHE_KEY_FORMAT, $targetConversationId);
            $messages = $this->distributedCacheClient->get($cacheKey) ?? array();

            $messages[] = array("role" => "user", "text" => $prompt);

            $response = $this->doGetResponse($messages, $responseJsonSchema);
            if ($response === null) {
                return null;
            }

            $messages[] = array("role" => "model", "text" => $response);
            $this->distributedCacheClient->set($cacheKey, $messages, self::CONVERSATION_CACHE_TTL);

            return new GenerativeContentResult($response, $targetConversationId);
        }

        private function doGetResponse(array $messages, ?array $responseJsonSchema = null) : ?string {
            $payload = array("messages" => $messages);
            if ($responseJsonSchema !== null) {
                $payload["schema"] = $responseJsonSchema;
            }

            $response = $this->httpClient->executeRequest(
                HttpMethod::POST,
                $this->getCortexBaseUrl() . self::GENERATIVE_CONTENT_API_ENDPOINT_PATH,
                array("Authorization: Bearer " . $this->authenticationService->getServiceAccessToken(), "Content-Type: application/json"),
                json_encode($payload)
            );

            // TODO: This is dangerous - the response schema can contain the 'message' field -> instead, check that if the response is array, then it matches the schema.
            if (is_array($response) && isset($response["message"])) {
                $this->logger->error("The generative content request was not successful. Reason: " . $response["message"]);
                return null;
            }

            // Deserialization must be handled by callers, the contract of the method is to always return string.
            $convertedResponse = is_array($response) ? json_encode($response) : $response;

            $this->logger->debug("The generative content request was successful. Prompt: '" . $messages[count($messages) - 1]["text"] . "' Response: '" . $convertedResponse . "'");
            return $convertedResponse;
        }

        private function createPrompt(string $query, array $context) : string {
            return str_replace(
                array_map(fn($key) => sprintf(self::KEY_PLACEHOLDER_FORMAT, $key), array_keys($context)),
                array_values($context),
                $query
            );
        }

        private function getCortexBaseUrl() : string {
            return "http://" . $this->cortexHost . ":" . $this->cortexPort;
        }
    }
?>
