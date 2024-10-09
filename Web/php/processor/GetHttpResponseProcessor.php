<?php
    class GetHttpResponseProcessor extends Processor {        
        public function process($input) {
            $curl = curl_init($input["url"]);

            $includeHeaders = isset($input["includeHeaders"]) && $input["includeHeaders"] == "true";

            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $input["method"]);
            curl_setopt($curl, CURLOPT_HEADER, $includeHeaders);
            curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1');
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($curl, CURLOPT_TIMEOUT, 300);
            curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE); 
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
    
            if (isset($input["payload"])) {
                curl_setopt($curl, $input["method"] == "GET" ? CURLOPT_GETFIELDS : CURLOPT_POSTFIELDS, $input["payload"]);
            }
    
            if (isset($input["headers"])) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, explode(",", $input["headers"]));
            }        
            
            $response = curl_exec($curl);

            $returnType = explode(";", curl_getinfo($curl, CURLINFO_CONTENT_TYPE))[0];
            $isJsonResponse = $returnType == "application/json";

            curl_close($curl);

            if ($includeHeaders && $isJsonResponse) {  
                list($header, $body) = explode("\r\n\r\n", $response, 2);  
                $result = json_decode($body, TRUE);
                $result["__httpHeaders"] = $this->parseHeaders($header);
                return $result;
            }

            return $isJsonResponse ? json_decode($response, TRUE) : $response;
        }

        public function getRequiredArguments() {
            return array("method", "url");
        }
        
        public function requiresAdminRole() {
            return TRUE;
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