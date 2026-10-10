<?php
    namespace Common\Service\Authentication;

    use Common\Client\Cache\CacheClient;
    use Common\Client\Http\HttpClient;
    use Common\Client\Http\HttpMethod;
    use Firebase\JWT\JWK;
    use Firebase\JWT\JWT;

    class AuthenticationService {
        
        private const JWKS_API_ENDPOINT_PATH = "/certificates/jwks";
        private const USER_ROLES_API_ENDPOINT_PATH_FORMAT = "/users/%s/roles";

        private const JWKS_KEYS_CACHE_KEY = "AuthenticationService:JwksKeys";
        private const JWKS_KEYS_CACHE_TTL = 24 * 3600;
        
        private const USER_ROLES_CACHE_KEY_FORMAT = "AuthenticationService:UserRoles:%s";
        private const USER_ROLES_CACHE_TTL = 60;

        private readonly CacheClient $distributedCacheClient;
        private readonly HttpClient $httpClient;

        private readonly string $iamAppClientId;
        private readonly string $iamHost;
        private readonly string $iamPort;

        public function __construct(CacheClient $distributedCacheClient, HttpClient $httpClient, string $iamAppClientId, string $iamHost, string $iamPort) {
            $this->distributedCacheClient = $distributedCacheClient;
            $this->httpClient = $httpClient;
            $this->iamAppClientId = $iamAppClientId;
            $this->iamHost = $iamHost;
            $this->iamPort = $iamPort;
        }
        
        public function authenticate(string $accessToken) : UserInfo {
            try {
                $keys = JWK::parseKeySet($this->getJwksKeys());
                $decoded = JWT::decode($accessToken, $keys);
                return new UserInfo($decoded->sub, $decoded->azp, isset($decoded->resource_access->{$this->iamAppClientId}->roles) 
                    ? $decoded->resource_access->{$this->iamAppClientId}->roles : array());
            }
            catch (\Throwable $e) {
                throw new AuthenticationException("An error occurred when decoding JWT token. " . $e->getMessage() . ".");
            }
        }

        public function getUserRoles(string $userId, string $accessToken) : array {
            $cacheKey = sprintf(self::USER_ROLES_CACHE_KEY_FORMAT, $userId);

            $cachedRoles = $this->distributedCacheClient->get($cacheKey);
            if ($cachedRoles !== null) {
                return $cachedRoles;
            }

            $response = $this->httpClient->executeRequest(HttpMethod::GET, $this->getIamBaseUrl() . sprintf(self::USER_ROLES_API_ENDPOINT_PATH_FORMAT, $userId),
                array("Authorization: Bearer " . $accessToken));

            if (!is_array($response)) {
                throw new \RuntimeException("The response with user roles is not an array. Response: " . json_encode($response));
            }

            $this->distributedCacheClient->set($cacheKey, $response, self::USER_ROLES_CACHE_TTL);
            return $response;
        }

        private function getJwksKeys() : mixed {
            $cachedJwksKeys = $this->distributedCacheClient->get(self::JWKS_KEYS_CACHE_KEY);
            if ($cachedJwksKeys !== null) {
                return $cachedJwksKeys;
            }

            $response = $this->httpClient->executeRequest(HttpMethod::GET, $this->getIamBaseUrl() . self::JWKS_API_ENDPOINT_PATH);
            if (!is_array($response) || !isset($response["keys"]) || !is_array($response["keys"]) || count($response["keys"]) === 0) {
                throw new \RuntimeException("There is no JWKS key. Response: " . json_encode($response));
            }

            $this->distributedCacheClient->set(self::JWKS_KEYS_CACHE_KEY, $response, self::JWKS_KEYS_CACHE_TTL);
            return $response;
        }
        
        private function getIamBaseUrl() : string {
            return "http://" . $this->iamHost . ":" . $this->iamPort;
        }
    }
?>