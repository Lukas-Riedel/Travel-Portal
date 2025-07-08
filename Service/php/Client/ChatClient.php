<?php
    class ChatClient {        
        public function getResponse($query) : ?string {
            global $configuration, $httpClient;

            $payload = array(
                "contents" => array(array(
                    "parts" => array(array(
                        "text" => $query)))));

            try {                
                $response = $httpClient->executeRequest(HttpMethod::POST, "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite-preview-06-17:generateContent?key=" . $configuration["googleGeminiApiKey"],
                    array("Content-Type: application/json"), json_encode($payload))["candidates"][0]["content"]["parts"][0]["text"];

                if ($response != NULL) {
                    $response = trim($response);
                }

                return $response;
            }
            catch (Throwable $e) {
                return NULL;
            }
        }
    }
?>