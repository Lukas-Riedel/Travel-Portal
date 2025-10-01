<?php
    namespace Core\Service\Authentication;

    use Core\Client\Cache\CacheClient;
    use Core\Client\Database\DatabaseClient;
    use Core\Service\Configuration\ConfigurationService;
    use Google\Auth\Credentials\ServiceAccountCredentials;
    use Core\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;
    use Firebase\JWT\JWT;
    use Firebase\JWT\Key;

    class AuthenticationService {
        
        private const GOOGLE_API_ACCESS_TOKEN_CACHE_KEY = "AuthenticationService:GoogleApiAccessToken";
        private const GOOGLE_FCM_ACCESS_TOKEN_CACHE_KEY = "AuthenticationService:GoogleFcmAccessToken";
        private const IBM_CLOUD_ACCESS_TOKEN_CACHE_KEY = "AuthenticationService:IbmCloudAccessToken";
        private const IAM_SERVICE_ACCESS_TOKEN_CACHE_KEY = "AuthenticationService:IamServiceAccessToken";

        private const GOOGLE_API_IAM_URL = "https://oauth2.googleapis.com/token";
        private const IBM_CLOUD_IAM_URL = "https://iam.test.cloud.ibm.com/identity/token";

        private const IAM_ACCESS_TOKEN_API_ENDPOINT_PATH = "/protocol/openid-connect/token";
        private const CLIENT_API_ENDPOINT_PATH_FORMAT = "/clients?clientId=%s";
        private const USERS_WITH_CLIENT_ROLE_API_ENDPOINT_PATH_FORMAT = "/clients/%s/roles/%s/users";

        private const IAM_SERVICE_ACCESS_TOKEN_GRANT_TYPE = "client_credentials";
        private const IAM_SERVICE_REFRESH_TOKEN_GRANT_TYPE = "refresh_token";
        private const IAM_SERVICE_CREDENTIALS_GRANT_TYPE = "password";

        private const EXTERNAL_ACCESS_TOKENS_VALIDITY_MULTIPLIER = 0.95;
        
        private const GOOGLE_FCM_ACCOUNT_TYPE = "service_account";
        private const GOOGLE_FCM_AUTH_URL = "https://accounts.google.com/o/oauth2/auth";
        private const GOOGLE_FCM_TOKEN_URL = "https://oauth2.googleapis.com/token";
        private const GOOGLE_FCM_AUTH_PROVIDER_X509_CERTIFICATE_URL = "https://www.googleapis.com/oauth2/v1/certs";
        private const GOOGLE_FCM_CLIENT_X509_CERTIFICATE_URL = "https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40travel-blog-free.iam.gserviceaccount.com";
        private const GOOGLE_FCM_UNIVERSE_DOMAIN = "googleapis.com";

        private const GOOGLE_API_REFRESH_TOKEN_GRANT_TYPE = "refresh_token";
        private const GOOGLE_API_AUTHORIZATION_CODE_GRANT_TYPE = "authorization_code";
        private const GOOGLE_API_ACCESS_TYPE = "offline";

        private const IBM_CLOUD_GRANT_TYPE = "urn:ibm:params:oauth:grant-type:apikey";
        private const IBM_CLOUD_RESPONSE_TYPE = "cloud_iam";
        
        private const GOOGLE_REFRESH_TOKEN_FILE_PATH = __DIR__ . "/../../../../config/google.txt";

        public const GOOGLE_API_AUTHORIZATION_SCOPES = array(
            "https://www.googleapis.com/auth/photoslibrary.appendonly",
            "https://www.googleapis.com/auth/photoslibrary.readonly.appcreateddata",
            "https://www.googleapis.com/auth/photoslibrary.edit.appcreateddata",
            "https://www.googleapis.com/auth/fitness.activity.read",
            "https://www.googleapis.com/auth/fitness.location.read",
            "https://www.googleapis.com/auth/calendar",
            "https://www.googleapis.com/auth/drive",
            "openid",
            "email",
            "profile"
        );
        
        private const GOOGLE_FCM_AUTHORIZATION_SCOPES = array(
            "https://www.googleapis.com/auth/firebase.messaging",
            "https://www.googleapis.com/auth/cloud-platform",
        );

        private readonly ConfigurationService $configurationService;

        private readonly HttpClient $httpClient;
        private readonly CacheClient $cacheClient;

        public function __construct(ConfigurationService $configurationService, HttpClient $httpClient, CacheClient $cacheClient) {
            $this->configurationService = $configurationService;
            $this->httpClient = $httpClient;
            $this->cacheClient = $cacheClient;
        }

        public function authenticate(string $accessToken) : UserInfo {
            $decoded = JWT::decode($accessToken, new Key(JWKS_PUBLIC_KEY, "RS256"));
            return new UserInfo($decoded->sub, $decoded->resource_access->{IAM_APP_CLIENT_ID}->roles, 0);
        }

        public function getIamResponseWithCredentials(string $username, string $password) : IamResponse {
            $payload = array(
                "grant_type" => self::IAM_SERVICE_CREDENTIALS_GRANT_TYPE,
                "client_id" => IAM_APP_CLIENT_ID,
                "username" => $username,
                "password" => $password
            );

            $response = $this->httpClient->executeRequest(HttpMethod::POST, IAM_BASE_URL . self::IAM_ACCESS_TOKEN_API_ENDPOINT_PATH,
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));
            return new IamResponse($response["access_token"], $response["expires_in"], $response["refresh_token"], $response["refresh_expires_in"]);
        }

        public function getIamResponseWithRefresh(string $refreshToken) : IamResponse {
            $payload = array(
                "grant_type" => self::IAM_SERVICE_REFRESH_TOKEN_GRANT_TYPE,
                "client_id" => IAM_APP_CLIENT_ID,
                "refresh_token" => $refreshToken
            );

            $response = $this->httpClient->executeRequest(HttpMethod::POST, IAM_BASE_URL . self::IAM_ACCESS_TOKEN_API_ENDPOINT_PATH,
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));
            return new IamResponse($response["access_token"], $response["expires_in"], $response["refresh_token"], $response["refresh_expires_in"]);
        }

        public function getUserIdsWithRole(string $role) : array {
            $appRealClientId = $this->getRealClientId(IAM_APP_CLIENT_ID);
            $response = $this->httpClient->executeRequest(HttpMethod::GET, IAM_ADMIN_BASE_URL . sprintf(self::USERS_WITH_CLIENT_ROLE_API_ENDPOINT_PATH_FORMAT, $appRealClientId, $role),
                array("Authorization: Bearer " . $this->getServiceAccessToken()));
                
            if (!is_array($response)) {
                throw new \RuntimeException("The response with users is not an array. Response: " . json_encode($response));
            }

            return array_map(fn($user) => $user["id"], $response);
        }

        public function getGoogleApiAccessToken() : string {
            $cachedGoogleApiAccessToken = $this->cacheClient->get(self::GOOGLE_API_ACCESS_TOKEN_CACHE_KEY);
            if ($cachedGoogleApiAccessToken !== null) {
                return $cachedGoogleApiAccessToken;
            }

            $refreshToken = null;
            if (file_exists($this::GOOGLE_REFRESH_TOKEN_FILE_PATH)) {
                // TODO: Decrypt the contents.
                $refreshToken = trim(file_get_contents($this::GOOGLE_REFRESH_TOKEN_FILE_PATH));
            }
            else {
                throw new \RuntimeException("The refresh token has not been set yet.");
            }

            $payload = array(
                "client_id" => GOOGLE_API_CLIENT_ID,
                "client_secret" => GOOGLE_API_CLIENT_SECRET,
                "redirect_uri" => BASE_URL,
                "refresh_token" => $refreshToken,
                "grant_type" => self::GOOGLE_API_REFRESH_TOKEN_GRANT_TYPE,
                "access_type" => self::GOOGLE_API_ACCESS_TYPE
            );     

            $response = $this->httpClient->executeRequest(HttpMethod::POST, self::GOOGLE_API_IAM_URL, 
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }
            $this->cacheClient->set(self::GOOGLE_API_ACCESS_TOKEN_CACHE_KEY, $response["access_token"], $this->getExternalAccessTokenExpiration($response["expires_in"]));

            return $response["access_token"];
        }

        public function getGoogleFcmAccessToken() : string {
            $cachedGoogleFcmAccessToken = $this->cacheClient->get(self::GOOGLE_FCM_ACCESS_TOKEN_CACHE_KEY);
            if ($cachedGoogleFcmAccessToken !== null) {
                return $cachedGoogleFcmAccessToken;
            }

            $credentials = array(
                "type" => self::GOOGLE_FCM_ACCOUNT_TYPE,
                "project_id" => FCM_PROJECT_ID,
                "private_key_id" => FCM_PRIVATE_KEY_ID,
                "private_key" => FCM_PRIVATE_KEY,
                "client_email" => FCM_CLIENT_EMAIL,
                "client_id" => FCM_CLIENT_ID,
                "auth_uri" => self::GOOGLE_FCM_AUTH_URL,
                "token_uri" => self::GOOGLE_FCM_TOKEN_URL,
                "auth_provider_x509_cert_url" => self::GOOGLE_FCM_AUTH_PROVIDER_X509_CERTIFICATE_URL,
                "client_x509_cert_url" => self::GOOGLE_FCM_CLIENT_X509_CERTIFICATE_URL,
                "universe_domain" => self::GOOGLE_FCM_UNIVERSE_DOMAIN,
            );
            
            $response = (new ServiceAccountCredentials(self::GOOGLE_FCM_AUTHORIZATION_SCOPES, $credentials))->fetchAuthToken();

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }
            $this->cacheClient->set(self::GOOGLE_FCM_ACCESS_TOKEN_CACHE_KEY, $response["access_token"], $this->getExternalAccessTokenExpiration($response["expires_in"]));

            return $response["access_token"];
        }

        public function getIbmCloudAccessToken() : string {
            $cacheIbmCloudAccessToken = $this->cacheClient->get(self::IBM_CLOUD_ACCESS_TOKEN_CACHE_KEY);
            if ($cacheIbmCloudAccessToken !== null) {
                return $cacheIbmCloudAccessToken;
            }

            $payload = array(
                "apikey" => IBM_CLOUD_API_KEY,
                "response_type" => self::IBM_CLOUD_RESPONSE_TYPE,
                "grant_type" => self::IBM_CLOUD_GRANT_TYPE
            );     

            $response = $this->httpClient->executeRequest(HttpMethod::POST, self::IBM_CLOUD_IAM_URL, 
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }
            $this->cacheClient->set(self::IBM_CLOUD_ACCESS_TOKEN_CACHE_KEY, $response["access_token"], $this->getExternalAccessTokenExpiration($response["expires_in"]));

            return $response["access_token"];
        }

        public function fetchGoogleApiRefreshToken(string $code) : void {
            $payload = array(
                "code" => $code,
                "client_id" => GOOGLE_API_CLIENT_ID,
                "client_secret" => GOOGLE_API_CLIENT_SECRET,
                "redirect_uri" => BASE_URL,
                "grant_type" => self::GOOGLE_API_AUTHORIZATION_CODE_GRANT_TYPE,
                "access_type" => self::GOOGLE_API_ACCESS_TYPE
            );

            $response = $this->httpClient->executeRequest(HttpMethod::POST, "https://oauth2.googleapis.com/token",
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["refresh_token"])) {
                throw new \RuntimeException("The refresh token could not be obtained. Response: " . json_encode($response));
            }
                
            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }
                
            $userInfo = $this->httpClient->executeRequest(HttpMethod::GET, "https://www.googleapis.com/oauth2/v3/userinfo", 
                array("Authorization: Bearer " . $response["access_token"]));

            if (!isset($userInfo["email"])) {
                throw new \RuntimeException("The email could not be obtained. Response: " . json_encode($userInfo));
            }

            if ($userInfo["email"] !== $this->configurationService->getConfigurationEntry("contactDetails")["email"]) {
                throw new \RuntimeException("The user with the e-mail '" . $userInfo["email"] . "' is not authorized.");
            }

            // TODO: Encrypt the contents.
            file_put_contents($this::GOOGLE_REFRESH_TOKEN_FILE_PATH, $response["refresh_token"]);
        }

        private function getRealClientId(string $clientId) : string {
            $response = $this->httpClient->executeRequest(HttpMethod::GET, IAM_ADMIN_BASE_URL . sprintf(self::CLIENT_API_ENDPOINT_PATH_FORMAT, $clientId),
                array("Authorization: Bearer " . $this->getServiceAccessToken()));

            if (!is_array($response) || count($response) !== 1 || !isset($response[0]["id"])) {
                throw new \RuntimeException("There must be exactly one client with the specified identifier. Response: " . json_encode($response));
            }

            return $response[0]["id"];
        }

        private function getServiceAccessToken() : string {
            $cachedServiceAccessToken = $this->cacheClient->get(self::IAM_SERVICE_ACCESS_TOKEN_CACHE_KEY);
            if ($cachedServiceAccessToken !== null) {
                return $cachedServiceAccessToken;
            }
            
            $payload = array(
                "client_id" => IAM_BACKEND_CLIENT_ID,
                "client_secret" => IAM_BACKEND_CLIENT_SECRET,
                "grant_type" => self::IAM_SERVICE_ACCESS_TOKEN_GRANT_TYPE,
            ); 

            $response = $this->httpClient->executeRequest(HttpMethod::POST, IAM_BASE_URL . self::IAM_ACCESS_TOKEN_API_ENDPOINT_PATH, 
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));
                
            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }

            $this->cacheClient->set(self::IAM_SERVICE_ACCESS_TOKEN_CACHE_KEY, $response["access_token"], $this->getExternalAccessTokenExpiration($response["expires_in"]));

            return $response["access_token"];
        }

        private function getExternalAccessTokenExpiration(int $expiration) : int {
            return round(self::EXTERNAL_ACCESS_TOKENS_VALIDITY_MULTIPLIER * $expiration);
        }
    }
?>