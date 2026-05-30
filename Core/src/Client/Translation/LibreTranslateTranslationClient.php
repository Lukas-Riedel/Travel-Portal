<?php
    namespace Core\Client\Translation;

    use Common\Client\Cache\CacheClient;
    use Common\Client\Http\HttpMethod;
    use Common\Client\Http\HttpClient;
    use Core\Common\CommonConstants;

    class LibreTranslateTranslationClient implements TranslationClient {

        private const TRANSLATION_API_ENDPOINT_PATH = "/translate";
        
        private const TRANSLATED_TEXT_CACHE_KEY_FORMAT = "LibreTranslateTranslationClient:TranslatedText:%s:%s:%s";
        private const TRANSLATED_TEXT_CACHE_TTL = CommonConstants::ONE_YEAR_SECONDS;

        private readonly HttpClient $httpClient;
        private readonly CacheClient $distributedCacheClient;
        
        private readonly string $libreTranslateHost;
        private readonly int $libreTranslatePort;

        public function __construct(HttpClient $httpClient, CacheClient $distributedCacheClient, string $libreTranslateHost, int $libreTranslatePort) {
            $this->httpClient = $httpClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->libreTranslateHost = $libreTranslateHost;
            $this->libreTranslatePort = $libreTranslatePort;
        }
        
        public function translate(string $text, string $sourceLanguage, string $targetLanguage) : string {
            $cacheKey = sprintf(self::TRANSLATED_TEXT_CACHE_KEY_FORMAT, $sourceLanguage, $targetLanguage, hash("sha256", $text));
            $cachedTranslation = $this->distributedCacheClient->get($cacheKey, self::TRANSLATED_TEXT_CACHE_TTL);
            if ($cachedTranslation !== null) {
                return $cachedTranslation;
            }

            $payload = array(
                "q" => $text,
                "source" => $sourceLanguage,
                "target" => $targetLanguage,
                "format" => "text"
            );

            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getlibreTranslateBaseUrl()
                . sprintf(self::TRANSLATION_API_ENDPOINT_PATH, $sourceLanguage, $targetLanguage),
                array("Content-Type: application/json"), json_encode($payload));
                
            if ($response === false) {
                return $text;
            }

            $this->distributedCacheClient->set($cacheKey, $response["translatedText"], self::TRANSLATED_TEXT_CACHE_TTL);
            return $response["translatedText"];
        }

        private function getlibreTranslateBaseUrl() : string {
            return "http://" . $this->libreTranslateHost . ":" . $this->libreTranslatePort;
        }
    }