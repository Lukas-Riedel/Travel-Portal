<?php
    namespace Common\Client\Http;

    use Monolog\Logger;

    class HttpClient {
    
        private const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";
        private const DEFAULT_HEADERS = array(
            "Accept: */*",
            "Accept-Language: en-US,en;q=0.9",
            "Connection: keep-alive",
            "Cache-Control: max-age=0",
        );

        private readonly Logger $logger;

        public function __construct(Logger $logger) {
            $this->logger = $logger;
        }

        // TODO: Replace cURL with Guzzle.
        public function executeRequest(HttpMethod $method, string $url, array $headers = array(), mixed $payload = null, bool $includeResponseHeaders = false) : mixed {
            $this->logger->debug("Sending the external request to '{$method->value} {$url}'...", array("headers" => $headers, "payload" => $payload));

            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method->value);
            curl_setopt($curl, CURLOPT_HEADER, $includeResponseHeaders);
            curl_setopt($curl, CURLOPT_USERAGENT, self::USER_AGENT);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 300);
            curl_setopt($curl, CURLOPT_AUTOREFERER, true); 
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge(self::DEFAULT_HEADERS, $headers));
            curl_setopt($curl, CURLOPT_ENCODING, "");
    
            if ($payload !== null) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
            }
            
            $response = curl_exec($curl);

            $isJsonResponse = explode(";", curl_getinfo($curl, CURLINFO_CONTENT_TYPE))[0] === "application/json";

            curl_close($curl);

            $result = $response;
            if ($isJsonResponse) {
                if ($includeResponseHeaders) {
                    list($header, $body) = explode("\r\n\r\n", $response, 2);  
                    $result = json_decode($body, true);
                    $result["__httpHeaders"] = $this->parseHeaders($header);
                }
                else {
                    $result = json_decode($response, true);
                }
            }

            return $result;
        }

        private function parseHeaders($rawHeaders) {
            $headers = array();
            $key = "";
    
            foreach (explode("\n", $rawHeaders) as $index => $header) {
                $header = explode(':', $header, 2);
    
                if (isset($header[1])) {
                    if (!isset($headers[$header[0]])) {                        
                        $headers[$header[0]] = trim($header[1]);
                    }
                    else if (is_array($headers[$header[0]])) {
                        $headers[$header[0]] = array_merge($headers[$header[0]], array(trim($header[1])));
                    }
                    else {
                        $headers[$header[0]] = array_merge(array($headers[$header[0]]), array(trim($header[1])));
                    }    
                    $key = $header[0];
                }
                else {
                    if (substr($header[0], 0, 1) == "\t") {                        
                        $headers[$key] .= "\r\n\t" . trim($header[0]);
                    }
                    else if (!$key) {                        
                        $headers[0] = trim($header[0]);
                        trim($header[0]);
                    }
                }
            }
    
            return $headers;
        }
    }
?>