<?php
    namespace Core\Service\Embedding;

    use Common\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;
    use Core\Service\Authentication\AuthenticationService;

    // TODO: Transform to EmbeddingClient.
    class EmbeddingService {

        private const PHOTO_EMBEDDING_API_ENDPOINT_PATH = "/embeddings/photo";        
        // TODO: Do not hardcode the language here.
        private const TEXT_EMBEDDING_API_ENDPOINT_PATH = "/embeddings/text?language=cs";

        private readonly AuthenticationService $authenticationService;
        private readonly HttpClient $httpClient;

        private readonly string $cortexHost;
        private readonly int $cortexPort;

        public function __construct(AuthenticationService $authenticationService, HttpClient $httpClient, string $cortexHost, int $cortexPort) {
            $this->authenticationService = $authenticationService;
            $this->httpClient = $httpClient;
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
            $payload = array("data" => $text);
            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getCortexBaseUrl() . self::TEXT_EMBEDDING_API_ENDPOINT_PATH,
                array("Authorization: Bearer " . $this->authenticationService->getServiceAccessToken(), "Content-Type: application/json"), json_encode($payload));
            return isset($response["embedding"]) && is_array($response["embedding"]) ? $response["embedding"] : null;
        }

        private function getCortexBaseUrl() : string {
            return "http://" . $this->cortexHost . ":" . $this->cortexPort;
        }
    }
?>