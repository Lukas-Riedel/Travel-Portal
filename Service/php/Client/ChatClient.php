<?php
    class ChatClient {        

        private const LANGUAGE_CHAT_PROMPT_SUFFIX_FORMAT = "The response should be in the %s language.";

        public function getResponse($query) : ?string {
            global $configuration, $httpClient;

            $payload = array(
                "contents" => array(array(
                    "parts" => array(array(
                        "text" => $query . " " . sprintf(self::LANGUAGE_CHAT_PROMPT_SUFFIX_FORMAT, $configuration["defaultLanguage"]))))));

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