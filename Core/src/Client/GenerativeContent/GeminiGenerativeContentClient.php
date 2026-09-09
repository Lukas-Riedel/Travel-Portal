<?php
    namespace Core\Client\GenerativeContent;

    use Common\Client\Cache\CacheClient;
    use Common\Client\Http\HttpClient;
    use Common\Client\Http\HttpMethod;
    use Core\Common\CommonConstants;
    use Monolog\Logger;

    class GeminiGenerativeContentClient implements GenerativeContentClient {
        
        private const GET_MODELS_URL_FORMAT = "https://generativelanguage.googleapis.com/v1beta/models?key=%s";
        private const GENERATE_CONTENT_URL_FORMAT = "https://generativelanguage.googleapis.com/v1beta/%s:generateContent?key=%s";

        private const GENERATE_CONTENT_GENERATION_METHOD = "generateContent";
        private const MODEL_NAME = "gemini";
        private const MODEL_WORDS_WHITELIST = array("flash", "pro");
        private const MODEL_WORDS_BLACKLIST = array("image", "latest", "preview", "omni");

        private const KEY_PLACEHOLDER_FORMAT = "{%s}";

        private const MODELS_CACHE_KEY = "GeminiGenerativeContentClient:Models";
        private const MODELS_CACHE_TTL = CommonConstants::ONE_MONTH_SECONDS;

        private readonly HttpClient $httpClient;
        private readonly CacheClient $distributedCacheClient;
        private readonly Logger $logger;

        private readonly string $googleGeminiApiKey;

        public function __construct(HttpClient $httpClient, CacheClient $distributedCacheClient, Logger $logger, string $googleGeminiApiKey) {
            $this->httpClient = $httpClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->logger = $logger;
            $this->googleGeminiApiKey = $googleGeminiApiKey;
        }

        public function getResponse(string $query, array $context) : ?string {
             $payload = array(
                "contents" => array(
                    array(
                        "parts" => array(
                            array(
                                "text" => $this->createPrompt($query, $context)
                            )
                        )
                    )
                )
            );

            foreach ($this->getModels() as &$model) {
                try {                
                    return trim($this->httpClient->executeRequest(HttpMethod::POST, sprintf(self::GENERATE_CONTENT_URL_FORMAT, $model, $this->googleGeminiApiKey),
                        array("Content-Type: application/json"), json_encode($payload))["candidates"][0]["content"]["parts"][0]["text"]);
                }
                catch (\Throwable $e) {
                    $this->logger->error("The {$model} generative content request was not successful. Reason: " . $e->getMessage(), array("error" => $e));
                }                
            }

            return null;
        }

        private function createPrompt(string $query, array $context) : string {
            return str_replace(array_map(fn($key) => sprintf(self::KEY_PLACEHOLDER_FORMAT, $key), array_keys($context)), array_values($context), $query);
        }

        private function getModels() : array {
            $cachedModels = $this->distributedCacheClient->get(self::MODELS_CACHE_KEY);
            if ($cachedModels !== null) {
                return $cachedModels;
            }

            $response = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_MODELS_URL_FORMAT, $this->googleGeminiApiKey))["models"];
            $models = array_map(fn($model) => $model["name"], array_values(array_filter($response, fn($model) => isset($model["thinking"]) && $model["thinking"] && str_contains($model["name"], self::MODEL_NAME)
                && count(array_filter(self::MODEL_WORDS_WHITELIST, fn($allowed) => str_contains($model["name"], $allowed))) > 0
                && count(array_filter(self::MODEL_WORDS_BLACKLIST, fn($forbidden) => str_contains($model["name"], $forbidden))) === 0
                && in_array(self::GENERATE_CONTENT_GENERATION_METHOD, $model["supportedGenerationMethods"]))));
            
            usort($models, function($a, $b) {
                return version_compare($b, $a);
            });

            $this->distributedCacheClient->set(self::MODELS_CACHE_KEY, $models, self::MODELS_CACHE_TTL);

            return $models;
        }
    }
?>