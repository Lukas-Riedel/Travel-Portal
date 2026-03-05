<?php
    namespace Common\Client\Http;

    use Common\CommonConstants;
    use Common\LoggingContext;
    use Monolog\Logger;

    // TODO: Replace cURL with Guzzle.
    class HttpClient {

        private const MAX_PAYLOAD_LOG_LENGTH = 1000;
    
        private const USER_AGENT = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36";
        private const DEFAULT_HEADERS = array(
            "Accept: */*",
            "Accept-Language: en-US,en;q=0.9",
            "Connection: keep-alive",
            "Cache-Control: max-age=0"
        );
        
        private readonly LoggingContext $loggingContext;
        private readonly Logger $logger;

        private readonly string $appName;

        public function __construct(string $appName, LoggingContext $loggingContext, Logger $logger) {
            $this->appName = $appName;
            $this->loggingContext = $loggingContext;
            $this->logger = $logger;
        }

        public function executeRequest(HttpMethod $method, string $url, array $headers = array(), mixed $payload = null, bool $includeResponseHeaders = false) : mixed {
            $loggedPayload = $payload !== null && is_string($payload) && strlen($payload) > self::MAX_PAYLOAD_LOG_LENGTH ? substr($payload, 0, self::MAX_PAYLOAD_LOG_LENGTH) . "... [TRUNCATED]" : $payload;
            $this->logger->debug("Sending the external request to '{$method->value} {$url}'...", array("headers" => $headers, "payload" => $loggedPayload));

            $curl = $this->prepareCurl($method, $url, $headers, $includeResponseHeaders);
    
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

        public function returns2xx(HttpMethod $method, string $url) : bool {
            $this->logger->debug("Sending the external request to '{$method->value} {$url}'...");

            $curl = $this->prepareCurl($method, $url);

            curl_exec($curl);
            
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            curl_close($curl);

            return $httpCode >= 200 && $httpCode < 300;
        }

        private function prepareCurl(HttpMethod $method, string $url, array $headers = array(), bool $includeResponseHeaders = false) : mixed {
            $curl = curl_init($url);

            $finalHeaders = array_merge(self::DEFAULT_HEADERS, $headers);
            
            $transactionId = $this->loggingContext->getTransactionId();
            if ($transactionId !== null) {
                $finalHeaders[] = CommonConstants::TRANSACTION_ID_HEADER . ": " . $transactionId;
            }

            $finalHeaders[] = CommonConstants::REQUEST_ORIGIN_HEADER . ": " . $this->appName;

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method->value);
            curl_setopt($curl, CURLOPT_HEADER, $includeResponseHeaders);
            curl_setopt($curl, CURLOPT_USERAGENT, self::USER_AGENT);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 300);
            curl_setopt($curl, CURLOPT_AUTOREFERER, true); 
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $finalHeaders);
            curl_setopt($curl, CURLOPT_ENCODING, "");

            return $curl;
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