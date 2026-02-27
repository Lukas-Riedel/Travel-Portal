<?php
    namespace Core\Service\Authentication;

    use Common\Service\Authentication\AuthenticationException;
    use Common\Client\Cache\CacheClient;
    use Common\Client\Http\HttpMethod;
    use Common\Service\Authentication\UserRole;
    use Core\Client\Http\HttpClient;

    class AuthenticationService {
        
        private const SERVICE_ACCESS_TOKEN_CACHE_KEY = "AuthenticationService:ServiceAccessToken";
        private const GOOGLE_API_ACCESS_TOKEN_CACHE_KEY = "AuthenticationService:GoogleApiAccessToken";
        private const GOOGLE_FCM_ACCESS_TOKEN_CACHE_KEY = "AuthenticationService:GoogleFcmAccessToken";
        private const IBM_CLOUD_ACCESS_TOKEN_CACHE_KEY = "AuthenticationService:IbmCloudAccessToken";

        private const TOKEN_API_ENDPOINT_PATH = "/token";
        private const IBM_CLOUD_TOKEN_API_ENDPOINT_PATH = "/ibmcloud/token";
        private const GOOGLE_API_TOKEN_API_ENDPOINT_PATH = "/google/token/api";
        private const GOOGLE_FCM_TOKEN_API_ENDPOINT_PATH = "/google/token/fcm";

        private const USERS_WITH_ROLE_API_ENDPOINT_PATH_FORMAT = "/users?role=%s";

        private const EXTERNAL_ACCESS_TOKENS_VALIDITY_MULTIPLIER = 0.95;

        private readonly HttpClient $httpClient;
        private readonly CacheClient $distributedCacheClient;

        private readonly string $iamBackendClientId;
        private readonly string $iamBackendClientSecret;
        private readonly string $iamHost;
        private readonly string $iamPort;

        public function __construct(HttpClient $httpClient, CacheClient $distributedCacheClient, string $iamBackendClientId, string $iamBackendClientSecret, string $iamHost, string $iamPort) {
            $this->httpClient = $httpClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->iamBackendClientId = $iamBackendClientId;
            $this->iamBackendClientSecret = $iamBackendClientSecret;
            $this->iamHost = $iamHost;
            $this->iamPort = $iamPort;
        }

        public function getUserIdsWithRole(UserRole $role) : array {
            $response = $this->httpClient->executeRequest(HttpMethod::GET, $this->getIamBaseUrl() . sprintf(self::USERS_WITH_ROLE_API_ENDPOINT_PATH_FORMAT, $role->value),
                array("Authorization: Bearer " . $this->getServiceAccessToken()));
                
            if (!is_array($response)) {
                throw new \RuntimeException("The response with user identifiers is not an array. Response: " . json_encode($response));
            }

            return $response;
        }

        public function getGoogleApiAccessToken() : string {
            $cachedGoogleApiAccessToken = $this->distributedCacheClient->get(self::GOOGLE_API_ACCESS_TOKEN_CACHE_KEY);
            if ($cachedGoogleApiAccessToken !== null) {
                return $cachedGoogleApiAccessToken;
            }

            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getIamBaseUrl() . self::GOOGLE_API_TOKEN_API_ENDPOINT_PATH,
                array("Authorization: Bearer " . $this->getServiceAccessToken()));

            if (!isset($response["accessToken"])) {
                throw new AuthenticationException("The access token could not be obtained. Response: " . json_encode($response));
            }

            $this->distributedCacheClient->set(self::GOOGLE_API_ACCESS_TOKEN_CACHE_KEY, $response["accessToken"], $this->getExternalAccessTokenExpiration($response["expiresIn"]));
            return $response["accessToken"];
        }

        public function getGoogleFcmAccessToken() : string {
            $cachedGoogleFcmAccessToken = $this->distributedCacheClient->get(self::GOOGLE_FCM_ACCESS_TOKEN_CACHE_KEY);
            if ($cachedGoogleFcmAccessToken !== null) {
                return $cachedGoogleFcmAccessToken;
            }

            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getIamBaseUrl() . self::GOOGLE_FCM_TOKEN_API_ENDPOINT_PATH,
                array("Authorization: Bearer " . $this->getServiceAccessToken()));

            if (!isset($response["accessToken"])) {
                throw new AuthenticationException("The access token could not be obtained. Response: " . json_encode($response));
            }

            $this->distributedCacheClient->set(self::GOOGLE_FCM_ACCESS_TOKEN_CACHE_KEY, $response["accessToken"], $this->getExternalAccessTokenExpiration($response["expiresIn"]));
            return $response["accessToken"];
        }

        public function getIbmCloudAccessToken() : string {
            $cachedIbmCloudAccessToken = $this->distributedCacheClient->get(self::IBM_CLOUD_ACCESS_TOKEN_CACHE_KEY);
            if ($cachedIbmCloudAccessToken !== null) {
                return $cachedIbmCloudAccessToken;
            }

            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getIamBaseUrl() . self::IBM_CLOUD_TOKEN_API_ENDPOINT_PATH,
                array("Authorization: Bearer " . $this->getServiceAccessToken()));

            if (!isset($response["accessToken"])) {
                throw new AuthenticationException("The access token could not be obtained. Response: " . json_encode($response));
            }

            $this->distributedCacheClient->set(self::IBM_CLOUD_ACCESS_TOKEN_CACHE_KEY, $response["accessToken"], $this->getExternalAccessTokenExpiration($response["expiresIn"]));
            return $response["accessToken"];
        }

        public function getServiceAccessToken() : string {
            $cachedServiceAccessToken = $this->distributedCacheClient->get(self::SERVICE_ACCESS_TOKEN_CACHE_KEY);
            if ($cachedServiceAccessToken !== null) {
                return $cachedServiceAccessToken;
            }

            $payload = array(
                "clientId" => $this->iamBackendClientId,
                "clientSecret" => $this->iamBackendClientSecret
            );

            $response = $this->httpClient->executeRequest(HttpMethod::POST, $this->getIamBaseUrl() . self::TOKEN_API_ENDPOINT_PATH,
                array("Content-Type: application/json"), json_encode($payload));

            if (!isset($response["accessToken"])) {
                throw new AuthenticationException("The access token could not be obtained. Response: " . json_encode($response));
            }

            $this->distributedCacheClient->set(self::SERVICE_ACCESS_TOKEN_CACHE_KEY, $response["accessToken"], $this->getExternalAccessTokenExpiration($response["expiresIn"]));
            return $response["accessToken"];
        }

        private function getExternalAccessTokenExpiration(int $expiration) : int {
            return round(self::EXTERNAL_ACCESS_TOKENS_VALIDITY_MULTIPLIER * $expiration);
        }

        private function getIamBaseUrl() : string {
            return "http://" . $this->iamHost . ":" . $this->iamPort;
        }
    }
?>