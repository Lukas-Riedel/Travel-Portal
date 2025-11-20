<?php
    namespace Core\Client\GenerativeContent;

    use Common\Client\Http\HttpMethod;
    use Monolog\Logger;
    use Core\Client\Http\HttpClient;

    class GeminiGenerativeContentClient implements GenerativeContentClient {
        
        private const GENERATE_CONTENT_URL_FORMAT = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=%s";

        private const KEY_PLACEHOLDER_FORMAT = "{%s}";

        private readonly HttpClient $httpClient;

        private readonly Logger $logger;

        private readonly string $googleGeminiApiKey;

        public function __construct(HttpClient $httpClient, Logger $logger, string $googleGeminiApiKey) {
            $this->httpClient = $httpClient;
            $this->logger = $logger;
            $this->googleGeminiApiKey = $googleGeminiApiKey;
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
                $response = $this->httpClient->executeRequest(HttpMethod::POST, sprintf(self::GENERATE_CONTENT_URL_FORMAT, $this->googleGeminiApiKey),
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