<?php
    // TODO: Rename to GenerativeContentClient
    class ChatClient {
        public function getResponse($query, $context) : ?string {
            global $httpClient;

            $payload = array(
                "contents" => array(
                        array(
                            "parts" => array(
                                array(
                                    "text" => $this->createPrompt($query, $context)
                                )
                            )
                        )
                    )
                );

            try {                
                $response = $httpClient->executeRequest(HttpMethod::POST, "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite-preview-06-17:generateContent?key=" . GOOGLE_GEMINI_API_KEY,
                    array("Content-Type: application/json"), json_encode($payload))["candidates"][0]["content"]["parts"][0]["text"];

                if ($response != null) {
                    $response = trim($response);
                }

                return $response;
            }
            catch (Throwable $e) {
                // TODO: Log error, or handle error states in a better way.
                return null;
            }
        }

        private function createPrompt($query, $context) : string {
            $prompt = $query;
            foreach ($context as $key => $value) {
                $prompt = str_replace("{" . $key . "}", $value, $prompt);
            }
            return $prompt;
        }
    }
?>