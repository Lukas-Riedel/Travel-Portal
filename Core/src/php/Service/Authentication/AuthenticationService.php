<?php
    namespace Core\Service\Authentication;

    use Core\Client\Cache\CacheClient;
    use Core\Client\Database\DatabaseClient;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;

    class AuthenticationService {
        
        private const GOOGLE_API_ACCESS_TOKEN_CACHE_KEY = "AuthenticationService:GoogleApiAccessToken";
        private const IBM_CLOUD_ACCESS_TOKEN_CACHE_KEY = "AuthenticationService:IbmCloudAccessToken";

        private const GOOGLE_API_IAM_URL = "https://oauth2.googleapis.com/token";
        private const IBM_CLOUD_IAM_URL = "https://iam.test.cloud.ibm.com/identity/token";

        private const REFRESH_TOKEN_VALIDITY_MULTIPLIER = 7 * 24;
        private const BEARER_TOKEN_VALIDITY = CommonConstants::ONE_HOUR_SECONDS;
        private const ADMIN_USER_ID = "999";

        private const DELIMITER = "::";
        private const HASH_ALGORITHM = "sha256";
        private const CIPHER_ALGORITHM = "aes-256-cbc";
        private const ACCESS_TOKEN = "ACCESS_TOKEN";
        private const REFRESH_TOKEN = "REFRESH_TOKEN";
        
        private const GOOGLE_REFRESH_TOKEN_FILE_PATH = __DIR__ . "/../../../../config/google.txt";

        public const GOOGLE_API_AUTHORIZATION_SCOPES = array(
            "https://www.googleapis.com/auth/photoslibrary.appendonly",
            "https://www.googleapis.com/auth/photoslibrary.readonly.appcreateddata",
            "https://www.googleapis.com/auth/photoslibrary.edit.appcreateddata",
            "https://www.googleapis.com/auth/fitness.activity.read",
            "https://www.googleapis.com/auth/fitness.location.read",
            "https://www.googleapis.com/auth/calendar",
            "https://www.googleapis.com/auth/drive.file",
            "openid",
            "email",
            "profile"
        );

        private readonly AuthenticationMapper $authenticationMapper;

        private readonly ConfigurationService $configurationService;

        private readonly \HttpClient $httpClient;
        private readonly CacheClient $cacheClient;

        public function __construct(DatabaseClient $databaseClient, ConfigurationService $configurationService, \HttpClient $httpClient, CacheClient $cacheClient) {
            $this->authenticationMapper = new AuthenticationMapper($databaseClient);
            $this->configurationService = $configurationService;
            $this->httpClient = $httpClient;
            $this->cacheClient = $cacheClient;
        }

        public function getUser(string $userId) : ?User {
            return $this->authenticationMapper->selectUserById($userId);
        }

        public function getUsersWithRole(string $role) : array {
            return $this->authenticationMapper->selectUsersWithRole($role);
        }

        public function getAccessToken(string $accessToken) : AccessToken {
            $decoded = base64_decode($accessToken);
            if ($decoded === false) {
                throw new AuthenticationException("The access token could not be read.");
            }
    
            $parts = explode(self::DELIMITER, $decoded, 2);
            if (count($parts) !== 2) {
                throw new AuthenticationException("The access token could not be read.");
            }
            
            list($encryptedData, $iv) = $parts;
            $decrypted = openssl_decrypt($encryptedData, self::CIPHER_ALGORITHM, $this->getAccessTokenPrivatekey(), 0, $iv);
            if ($decrypted === false) {
                throw new AuthenticationException("The access token could not be read.");
            }
            
            $decodedAccessToken = json_decode($decrypted, true);
            if ($decodedAccessToken === null) {
                throw new AuthenticationException("The access token could not be read.");
            }
    
            if ($decodedAccessToken["expiration"] < time()) {
                throw new AuthenticationException("The access token expired at " . date(DATE_ATOM, $decodedAccessToken["expiration"]) . ".");
            }

            return new AccessToken($decodedAccessToken["userId"], $decodedAccessToken["roles"], $decodedAccessToken["expiration"]);
        }

        public function authenticateWithRefreshToken(string $refreshToken) : AuthenticationResult {
            if ($refreshToken === null) {
                throw new AuthenticationException("The refresh token was not provided.");
            }

            $decoded = base64_decode($refreshToken);
            if ($decoded === false) {
                throw new AuthenticationException("The refresh token could not be read.");
            }
    
            $parts = explode(self::DELIMITER, $decoded, 2);
            if (count($parts) !== 2) {
                throw new AuthenticationException("The refresh token could not be read.");
            }
            
            list($encryptedData, $iv) = $parts;
            $decrypted = openssl_decrypt($encryptedData, self::CIPHER_ALGORITHM, $this->getRefreshTokenPrivatekey(), 0, $iv);
            if ($decrypted === false) {
                throw new AuthenticationException("The refresh token could not be read.");
            }
            
            $decodedRefreshToken = json_decode($decrypted, true);
            if ($decodedRefreshToken === null) {
                throw new AuthenticationException("The refresh token could not be read.");
            }
    
            if ($decodedRefreshToken["expiration"] < time()) {
                throw new AuthenticationException("The refresh token expired at " . date(DATE_ATOM, $decodedRefreshToken["expiration"]) . ".");
            }

            return $this->generateAuthenticationResult($decodedRefreshToken["userId"], $decodedRefreshToken["roles"], self::BEARER_TOKEN_VALIDITY);
        }

        public function authenticateWithCredentials(string $username, string $password) : AuthenticationResult {
            $user = $this->authenticationMapper->selectUserByUsername($username);
            if ($user === null) {
                throw new AuthenticationException("The user '" . $username . "' was not found.");
            }

            if ($user->getPassword() === null) {
                // Set the password on the first call of the IAM endpoint for the specified user.
                // Sufficient for now, create a separate service for users if needed.
                $this->authenticationMapper->updateUserPassword($username, $password);
            }
            else if (!password_verify($password, $user->getPassword())) {
                throw new AuthenticationException("Password for the user '" . $username . "' is invalid.");
            }

            return $this->generateAuthenticationResult($user->getId(), $user->getRoles(), self::BEARER_TOKEN_VALIDITY);
        }

        public function authenticateWithApiKey(string $apiKey) : AuthenticationResult {
            $user = $this->authenticationMapper->selectUserByApiKey($apiKey);

            if ($user === null) {
                throw new AuthenticationException("No user for the provided API key was found.");
            }

            return $this->generateAuthenticationResult($user->getId(), $user->getRoles(), self::BEARER_TOKEN_VALIDITY);
        }

        public function authenticateAsAdmin(int $validity) : AuthenticationResult {
            return $this->generateAuthenticationResult(self::ADMIN_USER_ID, array("ADMIN", "USER"), $validity);
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
                "redirect_uri" => IAM_BASE_URL,
                "refresh_token" => $refreshToken,
                "grant_type" => "refresh_token",
                "access_type" => "offline"
            );     

            $response = $this->httpClient->executeRequest(\HttpMethod::POST, self::GOOGLE_API_IAM_URL, 
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }
            $this->cacheClient->set(self::GOOGLE_API_ACCESS_TOKEN_CACHE_KEY, $response["access_token"], $response["expires_in"]);

            return $response["access_token"];
        }

        public function getIbmCloudAccessToken() : string {
            $cacheIbmCloudAccessToken = $this->cacheClient->get(self::IBM_CLOUD_ACCESS_TOKEN_CACHE_KEY);
            if ($cacheIbmCloudAccessToken !== null) {
                return $cacheIbmCloudAccessToken;
            }

            $payload = array(
                "apikey" => IBM_CLOUD_API_KEY,
                "response_type" => "cloud_iam",
                "grant_type" => "urn:ibm:params:oauth:grant-type:apikey"
            );     

            $response = $this->httpClient->executeRequest(\HttpMethod::POST, self::IBM_CLOUD_IAM_URL, 
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }
            $this->cacheClient->set(self::IBM_CLOUD_ACCESS_TOKEN_CACHE_KEY, $response["access_token"], $response["expires_in"]);

            return $response["access_token"];
        }

        public function fetchGoogleApiRefreshToken(string $code) : void {
            $payload = array(
                "code" => $code,
                "client_id" => GOOGLE_API_CLIENT_ID,
                "client_secret" => GOOGLE_API_CLIENT_SECRET,
                "redirect_uri" => IAM_BASE_URL,
                "grant_type" => "authorization_code",
                "access_type" => "offline"
            );

            $response = $this->httpClient->executeRequest(\HttpMethod::POST, "https://oauth2.googleapis.com/token",
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["refresh_token"])) {
                throw new \RuntimeException("The refresh token could not be obtained. Response: " . json_encode($response));
            }
                
            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }
                
            $userInfo = $this->httpClient->executeRequest(\HttpMethod::GET, "https://www.googleapis.com/oauth2/v3/userinfo", 
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

        // TODO: Switch to JWT.
        private function generateAuthenticationResult(string $userId, array $roles, int $validity) : AuthenticationResult {
            $rawAccessToken = new AccessToken($userId, $roles, time() + $validity);
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER_ALGORITHM));
            $encrypted = openssl_encrypt(json_encode($rawAccessToken), self::CIPHER_ALGORITHM, $this->getAccessTokenPrivatekey(), 0, $iv);
            $accessToken = base64_encode($encrypted . self::DELIMITER . $iv);
            
            $rawRefreshToken = new AccessToken($userId, $roles, time() + self::REFRESH_TOKEN_VALIDITY_MULTIPLIER * $validity);
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::CIPHER_ALGORITHM));
            $encrypted = openssl_encrypt(json_encode($rawRefreshToken), self::CIPHER_ALGORITHM, $this->getRefreshTokenPrivatekey(), 0, $iv);
            $refreshToken = base64_encode($encrypted . self::DELIMITER . $iv);

            return new AuthenticationResult($accessToken, $refreshToken, $roles, $validity);
        }

        private function getAccessTokenPrivatekey() {
            return hash_hmac(self::HASH_ALGORITHM, self::ACCESS_TOKEN, PRIVATE_KEY);
        }

        private function getRefreshTokenPrivatekey() {
            return hash_hmac(self::HASH_ALGORITHM, self::REFRESH_TOKEN, PRIVATE_KEY);
        }
    }
?>