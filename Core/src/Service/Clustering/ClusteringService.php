<?php
    namespace Core\Service\Clustering;

    use Common\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;
    use Core\Service\Authentication\AuthenticationService;

    // TODO: Transform to ClusteringClient.
    class ClusteringService {

        private const EMBEDDINGS_CLUSTERING_API_ENDPOINT_PATH = "/clustering/embeddings";

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

        public function getEmbeddingsClusters(array $embeddings, int $clustersCount) : array {
            $payload = array("embeddings" => $embeddings, "clusters" => $clustersCount);
            return $this->httpClient->executeRequest(HttpMethod::POST, $this->getCortexBaseUrl() . self::EMBEDDINGS_CLUSTERING_API_ENDPOINT_PATH,
                array("Authorization: Bearer " . $this->authenticationService->getServiceAccessToken(), "Content-Type: application/json"), json_encode($payload));
        }

        private function getCortexBaseUrl() : string {
            return "http://" . $this->cortexHost . ":" . $this->cortexPort;
        }
    }
?>