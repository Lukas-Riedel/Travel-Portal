<?php
    class HttpClient {
        public function executeRequest(HttpMethod $method, $url, $headers = array(), $payload = null, $includeResponseHeaders = false) {
            global $logger;

            $logger->debug("Sending the external request to '{$method->value} {$url}'...", array("headers" => $headers, "payload" => $payload));

            $curl = curl_init($url);

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method->value);
            curl_setopt($curl, CURLOPT_HEADER, $includeResponseHeaders);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 300);
            curl_setopt($curl, CURLOPT_AUTOREFERER, true); 
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    
            if ($payload !== null) {
                curl_setopt($curl, $method === HttpMethod::GET ? CURLOPT_GETFIELDS : CURLOPT_POSTFIELDS, $payload);
            }     
            
            $response = curl_exec($curl);

            $returnType = explode(";", curl_getinfo($curl, CURLINFO_CONTENT_TYPE))[0];
            $isJsonResponse = $returnType === "application/json";

            curl_close($curl);

            if ($includeResponseHeaders && $isJsonResponse) {  
                list($header, $body) = explode("\r\n\r\n", $response, 2);  
                $result = json_decode($body, true);
                $result["__httpHeaders"] = $this->parseHeaders($header);
                return $result;
            }

            return $isJsonResponse ? json_decode($response, true) : $response;
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

    enum HttpMethod : string {
        case GET = "GET";
        case POST = "POST";
        case PATCH = "PATCH";
        case PUT = "PUT";
        case DELETE = "DELETE";
        case HEAD = "HEAD";
    }
?>