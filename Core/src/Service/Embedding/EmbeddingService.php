<?php
    namespace Core\Service\Embedding;

    use Common\Client\Cache\CacheClient;
    use Common\Client\Http\HttpMethod;
    use Common\Client\Http\HttpClient;
    use Core\Client\Translation\TranslationClient;
    use Core\Common\CommonConstants;
    use Core\Service\Authentication\AuthenticationService;

    // TODO: Transform to EmbeddingClient.
    class EmbeddingService {

        private const PHOTO_EMBEDDING_API_ENDPOINT_PATH = "/embeddings/photo";
        private const TEXT_EMBEDDING_API_ENDPOINT_PATH = "/embeddings/text";

        private const TEXT_EMBEDDING_CACHE_KEY_FORMAT = "EmbeddingService:TextEmbedding:%s";
        private const TEXT_EMBEDDING_CACHE_TTL = CommonConstants::ONE_YEAR_SECONDS;

        // TODO: Do not hardcode the source language here.
        private const TEXT_EMBEDDING_SOURCE_LANGUAGE = "cs";
        private const TEXT_EMBEDDING_TARGET_LANGUAGE = "en";

        private readonly AuthenticationService $authenticationService;
        private readonly HttpClient $httpClient;
        private readonly CacheClient $distributedCacheClient;
        private readonly TranslationClient $translationClient;

        private readonly string $cortexHost;
        private readonly int $cortexPort;

        public function __construct(AuthenticationService $authenticationService, HttpClient $httpClient, CacheClient $distributedCacheClient,
            TranslationClient $translationClient, string $cortexHost, int $cortexPort) {
            $this->authenticationService = $authenticationService;
            $this->httpClient = $httpClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->translationClient = $translationClient;
            $this->cortexHost = $cortexHost;
            $this->cortexPort = $cortexPort;
        }

        public function getPhotoEmbedding(string $base64Data) : ?array {
            $payload = array("data" => $base64Data);
            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getCortexBaseUrl() . self::PHOTO_EMBEDDING_API_ENDPOINT_PATH,
                array("Authorization: Bearer " . $this->authenticationService->getServiceAccessToken(), "Content-Type: application/json"), json_encode($payload));
            return isset($response["embedding"]) && is_array($response["embedding"]) ? $response["embedding"] : null;
        }
        
        public function getTextEmbedding(string $text) : ?array {
            $cacheKey = sprintf(self::TEXT_EMBEDDING_CACHE_KEY_FORMAT, hash("sha256", mb_strtolower($text)));
            $cachedEmbedding = $this->distributedCacheClient->get($cacheKey, self::TEXT_EMBEDDING_CACHE_TTL);
            if ($cachedEmbedding !== null) {
                return $cachedEmbedding;
            }

            $translatedText = $this->translationClient->translate($text, self::TEXT_EMBEDDING_SOURCE_LANGUAGE, self::TEXT_EMBEDDING_TARGET_LANGUAGE);
            $payload = array("data" => $translatedText);
            
            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getCortexBaseUrl() . self::TEXT_EMBEDDING_API_ENDPOINT_PATH,
                array("Authorization: Bearer " . $this->authenticationService->getServiceAccessToken(), "Content-Type: application/json"), json_encode($payload));
            if (!isset($response["embedding"]) || !is_array($response["embedding"])) {
                return null;
            }

            $this->distributedCacheClient->set($cacheKey, $response["embedding"], self::TEXT_EMBEDDING_CACHE_TTL);
            return $response["embedding"];
        }

        public function getEmbeddingSimilarity(array $a, array $b) : float {
            return array_sum(array_map(fn($v, $w) => $v * $w, $a, $b));
        }

        private function getCortexBaseUrl() : string {
            return "http://" . $this->cortexHost . ":" . $this->cortexPort;
        }
    }
?>