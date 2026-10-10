<?php
    namespace Iam\Service\User;

    use Common\Client\Cache\CacheClient;
    use Common\Client\Http\HttpClient;
    use Common\Client\Http\HttpMethod;
    use Common\Service\Authentication\UserRole;
    use Iam\Service\Token\TokenService;

    class UserService {

        private const CLIENT_API_ENDPOINT_PATH_FORMAT = "/clients?clientId=%s";
        private const USERS_WITH_CLIENT_ROLE_API_ENDPOINT_PATH_FORMAT = "/clients/%s/roles/%s/users";
        private const USER_CLIENT_ROLES_API_ENDPOINT_PATH_FORMAT = "/users/%s/role-mappings/clients/%s";

        private const CLIENT_ID_CACHE_KEY_FORMAT = "UserService:ClientId:%s";
        private const CLIENT_ID_CACHE_TTL = 86400;

        private const SERVICE_TOKEN_CACHE_KEY_FORMAT = "UserService:ServiceToken:%s";
        private const SERVICE_TOKEN_CACHE_TTL_MULTIPLIER = 0.95;

        private readonly TokenService $tokenService;
        private readonly HttpClient $httpClient;
        private readonly CacheClient $distributedCacheClient;

        private readonly string $iamAppClientId;
        private readonly string $iamBackendClientId;
        private readonly string $iamBackendClientSecret;
        private readonly string $internalAdminIamBaseUrl;

        public function __construct(TokenService $tokenService, HttpClient $httpClient, CacheClient $distributedCacheClient, string $iamAppClientId, string $iamBackendClientId, string $iamBackendClientSecret, string $internalAdminIamBaseUrl) {
            $this->tokenService = $tokenService;
            $this->httpClient = $httpClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->iamAppClientId = $iamAppClientId;
            $this->iamBackendClientId = $iamBackendClientId;
            $this->iamBackendClientSecret = $iamBackendClientSecret;
            $this->internalAdminIamBaseUrl = $internalAdminIamBaseUrl;
        }

        public function getUserIdsWithRole(UserRole $role) : array {
            $accessToken = $this->getServiceAccessToken();
            $clientId = $this->getClientId($accessToken);

            $response = $this->httpClient->executeRequest(HttpMethod::GET, $this->internalAdminIamBaseUrl . sprintf(self::USERS_WITH_CLIENT_ROLE_API_ENDPOINT_PATH_FORMAT, $clientId, $role->value),
                array("Authorization: Bearer " . $accessToken));
                
            if (!is_array($response)) {
                throw new \RuntimeException("The response with users is not an array. Response: " . json_encode($response));
            }

            return array_map(fn($user) => $user["id"], $response);
        }

        public function getUserRoles(string $userId) : array {
            $accessToken = $this->getServiceAccessToken();
            $clientId = $this->getClientId($accessToken);

            $response = $this->httpClient->executeRequest(HttpMethod::GET, $this->internalAdminIamBaseUrl . sprintf(self::USER_CLIENT_ROLES_API_ENDPOINT_PATH_FORMAT, $userId, $clientId),
                array("Authorization: Bearer " . $accessToken));

            if (!is_array($response)) {
                throw new \RuntimeException("The response with roles is not an array. Response: " . json_encode($response));
            }

            return array_map(fn($role) => $role["name"], $response);
        }

        private function getServiceAccessToken() : string {
            $cacheKey = sprintf(self::SERVICE_TOKEN_CACHE_KEY_FORMAT, $this->iamBackendClientId);

            $cachedToken = $this->distributedCacheClient->get($cacheKey);
            if ($cachedToken !== null) {
                return $cachedToken;
            }

            $iamResponse = $this->tokenService->getIamResponseWithClientCredentials($this->iamBackendClientId, $this->iamBackendClientSecret);
            $ttl = round($iamResponse->getExpiresIn() * self::SERVICE_TOKEN_CACHE_TTL_MULTIPLIER);
            $this->distributedCacheClient->set($cacheKey, $iamResponse->getAccessToken(), $ttl);
            return $iamResponse->getAccessToken();
        }

        private function getClientId(string $accessToken) : string {
            $cacheKey = sprintf(self::CLIENT_ID_CACHE_KEY_FORMAT, $this->iamAppClientId);

            $cachedId = $this->distributedCacheClient->get($cacheKey);
            if ($cachedId !== null) {
                return $cachedId;
            }

            $response = $this->httpClient->executeRequest(HttpMethod::GET, $this->internalAdminIamBaseUrl . sprintf(self::CLIENT_API_ENDPOINT_PATH_FORMAT, $this->iamAppClientId),
                array("Authorization: Bearer " . $accessToken));

            if (!is_array($response) || count($response) !== 1 || !isset($response[0]["id"])) {
                throw new \RuntimeException("There must be exactly one client with the specified identifier. Response: " . json_encode($response));
            }

            $this->distributedCacheClient->set($cacheKey, $response[0]["id"], self::CLIENT_ID_CACHE_TTL);
            return $response[0]["id"];
        }
    }
?>