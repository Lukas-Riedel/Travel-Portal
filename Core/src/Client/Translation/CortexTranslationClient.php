<?php
    namespace Core\Client\Translation;

    use Common\Client\Cache\CacheClient;
    use Common\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;
    use Core\Common\CommonConstants;
    use Core\Service\Authentication\AuthenticationService;

    class CortexTranslationClient implements TranslationClient {

        private const TRANSLATION_API_ENDPOINT_PATH_FORMAT = "/translate?text=%s&sourceLanguage=%s&targetLanguage=%s";
        
        private const TRANSLATED_TEXT_CACHE_KEY_FORMAT = "CortexTranslationClient:TranslatedText:%s:%s:%s";
        private const TRANSLATED_TEXT_CACHE_TTL = CommonConstants::ONE_YEAR_SECONDS;

        private readonly HttpClient $httpClient;
        private readonly CacheClient $distributedCacheClient;
        
        private readonly string $cortexHost;
        private readonly int $cortexPort;

        private ?AuthenticationService $authenticationService;

        public function __construct(HttpClient $httpClient, CacheClient $distributedCacheClient, string $cortexHost, int $cortexPort) {
            $this->httpClient = $httpClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->cortexHost = $cortexHost;
            $this->cortexPort = $cortexPort;
        }

        public function setAuthenticationService(AuthenticationService $authenticationService) {
            $this->authenticationService = $authenticationService;
        }
        
        public function translate(string $text, string $sourceLanguage, string $targetLanguage) : string {
            $cacheKey = sprintf(self::TRANSLATED_TEXT_CACHE_KEY_FORMAT, $sourceLanguage, $targetLanguage, $text);
            $cachedTranslation = $this->distributedCacheClient->get($cacheKey, self::TRANSLATED_TEXT_CACHE_TTL);
            if ($cachedTranslation !== null) {
                return $cachedTranslation;
            }

            $translation = $this->httpClient->executeRequest(HttpMethod::GET, $this->getCortexBaseUrl()
                . sprintf(self::TRANSLATION_API_ENDPOINT_PATH_FORMAT, urlencode($text), $sourceLanguage, $targetLanguage),
                array("Authorization: Bearer " . $this->authenticationService->getServiceAccessToken()));

            $this->distributedCacheClient->set($cacheKey, $translation, self::TRANSLATED_TEXT_CACHE_TTL);
            return $translation;
        }

        private function getCortexBaseUrl() : string {
            return "http://" . $this->cortexHost . ":" . $this->cortexPort;
        }
    }