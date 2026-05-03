<?php
    namespace Core\Client\Http;

    use Common\Client\Http\HttpMethod;
    use Monolog\Logger;

    class FlareSolverrHttpClientDecorator extends HttpClient {
        private const SEND_REQUEST_ENDPOINT = "/v1";
        private const OPENING_HTML_TAG_PREFIX = "<html";
        private const MAX_TIMEOUT = 60000;

        private readonly HttpClient $httpClient;
        private readonly Logger $logger;

        private readonly string $flareSolverrHost;
        private readonly int $flareSolverrPort;

        public function __construct(HttpClient $httpClient, string $flareSolverrHost, int $flareSolverrPort, Logger $logger) {
            $this->httpClient = $httpClient;
            $this->flareSolverrHost = $flareSolverrHost;
            $this->flareSolverrPort = $flareSolverrPort;
            $this->logger = $logger;
        }

        public function executeRequest(HttpMethod $method, string $url, array $headers = array(), mixed $payload = null, bool $includeResponseHeaders = false) : mixed {
            if ($method !== HttpMethod::GET) {
                // TODO: Implement support for other methods.
                $this->logger->error("The support for {$method->name} is not implemented.");
                return $this->httpClient->executeRequest($method, $url, $headers, $payload, $includeResponseHeaders);
            }

            $flareSolverrPayload = array(
                "cmd" => "request.get",
                "url" => $url,
                "maxTimeout" => self::MAX_TIMEOUT,
                "headers" => $headers
            );

            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getFlareSolverrBaseUrl() . self::SEND_REQUEST_ENDPOINT,
                array("Content-Type: application/json"), json_encode($flareSolverrPayload), $includeResponseHeaders);

            if (!isset($response["solution"]["response"])) {
                return null;
            }
            
            $rawResponse = $response["solution"]["response"];
            if (str_starts_with(trim($rawResponse), self::OPENING_HTML_TAG_PREFIX)) {
                return trim(html_entity_decode(strip_tags($rawResponse)));
            }
            
            return $rawResponse;
        }  

        private function getFlareSolverrBaseUrl() : string {
            return "http://" . $this->flareSolverrHost . ":" . $this->flareSolverrPort;
        }
    }
?>