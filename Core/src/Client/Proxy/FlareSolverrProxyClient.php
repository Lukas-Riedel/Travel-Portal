<?php
    namespace Core\Client\Proxy;

    use Common\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;

    class FlareSolverrProxyClient implements ProxyClient {

        private const SEND_REQUEST_ENDPOINT = "/v1";
        private const OPENING_HTML_TAG_PREFIX = "<html";
        private const MAX_TIMEOUT = 60000;

        private readonly HttpClient $httpClient;

        private readonly string $flareSolverrHost;
        private readonly int $flareSolverrPort;

        public function __construct(HttpClient $httpClient, string $flareSolverrHost, int $flareSolverrPort) {
            $this->httpClient = $httpClient;
            $this->flareSolverrHost = $flareSolverrHost;
            $this->flareSolverrPort = $flareSolverrPort;
        }
    
        public function get(string $url) : mixed {
            $payload = array(
                "cmd" => "request.get",
                "url" => $url,
                "maxTimeout" => self::MAX_TIMEOUT
            );

            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getFlareSolverrBaseUrl() . self::SEND_REQUEST_ENDPOINT,
                array("Content-Type: application/json"), json_encode($payload));

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