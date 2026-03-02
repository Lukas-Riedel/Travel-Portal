<?php
    namespace Core\Client\GenerativeContent;

    use Common\Client\Cache\CacheClient;
    use Core\Common\CommonConstants;

    class CachingGenerativeClient implements GenerativeContentClient {

        private const RESPONSE_CACHE_KEY_FORMAT = "CachingGenerativeClient:Response:%s";
        private const RESPONSE_CACHE_TTL = CommonConstants::ONE_MONTH_SECONDS;

        private readonly GenerativeContentClient $client;
        private readonly CacheClient $cacheClient;

        public function __construct(GenerativeContentClient $client, CacheClient $cacheClient) {
            $this->client = $client;
            $this->cacheClient = $cacheClient;
        }

        public function getResponse(string $query, array $context) : ?string {
            $cacheKey = sprintf(self::RESPONSE_CACHE_KEY_FORMAT, hash("sha256", json_encode(array("query" => $query, "context" => $context))));
            $cachedResponse = $this->cacheClient->get($cacheKey);
            if ($cachedResponse !== null) {
                return $cachedResponse;
            }

            $response = $this->client->getResponse($query, $context);
            if ($response !== null) {
                $this->cacheClient->set($cacheKey, $response, self::RESPONSE_CACHE_TTL);
            }
            
            return $response;
        }
    }