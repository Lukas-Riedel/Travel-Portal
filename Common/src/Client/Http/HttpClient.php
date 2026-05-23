<?php
    namespace Common\Client\Http;

    interface HttpClient {
        public function executeRequest(HttpMethod $method, string $url, array $headers = array(), mixed $payload = null, bool $includeResponseHeaders = false) : mixed;
        public function returns2xx(HttpMethod $method, string $url) : bool;
    }
?>