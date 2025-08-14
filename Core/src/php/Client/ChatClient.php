<?php
    class ChatClient {        

        private const LANGUAGE_CHAT_PROMPT_SUFFIX_FORMAT = "The response should be in the %s language.";

        public function getResponse($query) : ?string {
            global $httpClient, $configurationService;

            $payload = array(
                "contents" => array(array(
                    "parts" => array(array(
                        "text" => $query . " " . sprintf(self::LANGUAGE_CHAT_PROMPT_SUFFIX_FORMAT, $configurationService->getConfigurationEntry("generativeChat")["language"]))))));

            try {                
                $response = $httpClient->executeRequest(HttpMethod::POST, "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite-preview-06-17:generateContent?key=" . GOOGLE_GEMINI_API_KEY,
                    array("Content-Type: application/json"), json_encode($payload))["candidates"][0]["content"]["parts"][0]["text"];

                if ($response != null) {
                    $response = trim($response);
                }

                return $response;
            }
            catch (Throwable $e) {
                return null;
            }
        }
    }
?>