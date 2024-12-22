<?php
    require_once(dirname(__FILE__) . "/GetHttpResponseProcessor.php");

    class GetGoogleResponseProcessor extends Processor {        
        public function process($input) {
            $getHttpResponseProcessor = new GetHttpResponseProcessor();

            $headers = array('Authorization: Bearer ' . $this->getGoogleApiAccessToken($getHttpResponseProcessor));
            if (isset($input["payload"])) {
                if (isset($input["contentType"])) {
                    $headers[] = "Content-Type: " . $input["contentType"];
                }
                else {
                    $headers[] = "Content-Type: application/json";
                }
            }

            if (isset($input["headers"])) {
                foreach ($input["headers"] as $key => $value) {
                    $headers[] = $key . ": " . $value;
                }
            }

            return $getHttpResponseProcessor
                ->process(array(
                    "method" => $input["method"], 
                    "url" => $input["url"], 
                    "payload" => (isset($input["payload"]) ? $input["payload"] : NULL), 
                    "headers" => implode(",", $headers)));
        }

        public function getRequiredArguments() {
            return array("method", "url");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }

        private function getGoogleApiAccessToken($getHttpResponseProcessor) {
            global $configuration;

            if (isset($_SESSION["googleApiAccessToken"]) 
                && isset($_SESSION["googleApiAccessTokenExpiration"]) 
                && $_SESSION["googleApiAccessTokenExpiration"] >= time()) {
                return $_SESSION["googleApiAccessToken"];
            }
            
            $httpsEnabled = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on";
            $payload = array(
                "client_id" => $configuration["googleApiCredentials"]["clientId"],
                "client_secret" => $configuration["googleApiCredentials"]["clientSecret"],
                "redirect_uri" => BASE_URL,
                "refresh_token" => $configuration["googleApiCredentials"]["accessKey"],
                "grant_type" => "refresh_token",
                "access_type" => "offline");     

            $response = $getHttpResponseProcessor->process(array(
                "method" => "POST", 
                "url" => "https://oauth2.googleapis.com/token", 
                "payload" => http_build_query($payload), 
                "headers" => "Content-Type: application/x-www-form-urlencoded"));

            if (!isset($response["access_token"])) {
                throw new RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }

            $_SESSION["googleApiAccessToken"] = $response["access_token"];
            $_SESSION["googleApiAccessTokenExpiration"] = time() + $response["expires_in"];

            return $_SESSION["googleApiAccessToken"];
        }
    }
?>