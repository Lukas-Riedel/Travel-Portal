<?php
    namespace Core\Client\GenerativeContent;

    use Monolog\Logger;

    class GeminiGenerativeContentClient implements GenerativeContentClient {
        
        private const GENERATE_CONTENT_URL_FORMAT = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite-preview-06-17:generateContent?key=%s";

        private const KEY_PLACEHOLDER_FORMAT = "{%s}";

        private readonly \HttpClient $httpClient;

        private readonly Logger $logger;

        public function __construct(\HttpClient $httpClient, Logger $logger) {
            $this->httpClient = $httpClient;
            $this->logger = $logger;
        }

        public function getResponse(string $query, array $context) : ?string {
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

            $response = null;
            try {                
                $response = $this->httpClient->executeRequest(\HttpMethod::POST, sprintf(self::GENERATE_CONTENT_URL_FORMAT, GOOGLE_GEMINI_API_KEY),
                    array("Content-Type: application/json"), json_encode($payload))["candidates"][0]["content"]["parts"][0]["text"];
            }
            catch (\Throwable $e) {
                $this->logger->error("The generative content request was not successful. Reason: " . $e->getMessage(), array("response" => $response));
                return null;
            }

            if ($response != null) {
                $response = trim($response);
            }

            return $response;
        }

        private function createPrompt(string $query, array $context) : string {
            return str_replace(array_map(fn($key) => sprintf(self::KEY_PLACEHOLDER_FORMAT, $key), array_keys($context)), array_values($context), $query);
        }
    }
?>