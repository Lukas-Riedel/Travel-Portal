<?php
    namespace Service\Service\Authentication;

    use Service\Service\Configuration\ConfigurationService;

    class AuthenticationService {

        private const REFRESH_TOKEN_VALIDITY_MULTIPLIER = 3 * 24;
        private const BEARER_TOKEN_VALIDITY = 3600;
        private const ADMIN_USER_ID = "999";

        private const DELIMITER = "::";
        private const HASH_ALGORITHM = "sha256";
        private const CIPHER_ALGORITHM = "aes-256-cbc";
        private const ACCESS_TOKEN = "ACCESS_TOKEN";
        private const REFRESH_TOKEN = "REFRESH_TOKEN";

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

        public function __construct(\DatabaseProvider $databaseProvider, ConfigurationService $configurationService, \HttpClient $httpClient) {
            $this->authenticationMapper = new AuthenticationMapper($databaseProvider);
            $this->configurationService = $configurationService;
            $this->httpClient = $httpClient;
        }

        public function getUser(string $userId) : ?User {
            return $this->authenticationMapper->selectUserById($userId);
        }

        public function getUsersWithRoles(array $roles) : array {
            return $this->authenticationMapper->selectUsersWithRoles($roles);
        }

        public function getAccessToken(string $accessToken) : AccessToken {
            if ($accessToken === NULL) {
                throw new AuthenticationException("The access token was not provided.");
            }

            $decoded = base64_decode($accessToken);
            if ($decoded === FALSE) {
                throw new AuthenticationException("The access token could not be read.");
            }
    
            $parts = explode(self::DELIMITER, $decoded, 2);
            if (count($parts) !== 2) {
                throw new AuthenticationException("The access token could not be read.");
            }
            
            list($encryptedData, $iv) = $parts;
            $decrypted = openssl_decrypt($encryptedData, self::CIPHER_ALGORITHM, $this->getAccessTokenPrivatekey(), 0, $iv);
            if ($decrypted === FALSE) {
                throw new AuthenticationException("The access token could not be read.");
            }
            
            $decodedAccessToken = json_decode($decrypted, TRUE);
            if ($decodedAccessToken === NULL) {
                throw new AuthenticationException("The access token could not be read.");
            }
    
            if ($decodedAccessToken["expiration"] < time()) {
                throw new AuthenticationException("The access token expired at " . $decodedAccessToken["expiration"] . ".");
            }

            return new AccessToken($decodedAccessToken["userId"], $decodedAccessToken["roles"], $decodedAccessToken["expiration"]);
        }

        public function authenticateWithRefreshToken(string $refreshToken) : AuthenticationResult {
            if ($refreshToken === NULL) {
                throw new AuthenticationException("The refresh token was not provided.");
            }

            $decoded = base64_decode($refreshToken);
            if ($decoded === FALSE) {
                throw new AuthenticationException("The refresh token could not be read.");
            }
    
            $parts = explode(self::DELIMITER, $decoded, 2);
            if (count($parts) !== 2) {
                throw new AuthenticationException("The refresh token could not be read.");
            }
            
            list($encryptedData, $iv) = $parts;
            $decrypted = openssl_decrypt($encryptedData, self::CIPHER_ALGORITHM, $this->getRefreshTokenPrivatekey(), 0, $iv);
            if ($decrypted === FALSE) {
                throw new AuthenticationException("The refresh token could not be read.");
            }
            
            $decodedRefreshToken = json_decode($decrypted, TRUE);
            if ($decodedRefreshToken === NULL) {
                throw new AuthenticationException("The refresh token could not be read.");
            }
    
            if ($decodedRefreshToken["expiration"] < time()) {
                throw new AuthenticationException("The refresh token expired at " . $decodedRefreshToken["expiration"] . ".");
            }

            return $this->generateAuthenticationResult($decodedRefreshToken["userId"], $decodedRefreshToken["roles"], self::BEARER_TOKEN_VALIDITY);
        }

        public function authenticateWithCredentials(string $username, string $password) : AuthenticationResult {
            $user = $this->authenticationMapper->selectUserByUsername($username);
            if ($user === NULL) {
                throw new AuthenticationException("The user '" . $username . "' was not found.");
            }

            if ($user->getPassword() === NULL) {
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

            if ($user === NULL) {
                throw new AuthenticationException("No user for the provided API key was found.");
            }

            return $this->generateAuthenticationResult($user->getId(), $user->getRoles(), self::BEARER_TOKEN_VALIDITY);
        }

        public function authenticateAsAdmin(int $validity) : AuthenticationResult {
            return $this->generateAuthenticationResult(self::ADMIN_USER_ID, array("ADMIN", "USER"), $validity);
        }

        public function getGoogleApiAccessToken() : GoogleAuthenticationResult {            
            $refreshToken = NULL;
            if (file_exists($this->getGoogleRefreshTokenFilePath())) {
                // TODO: Decrypt the contents.
                $refreshToken = trim(file_get_contents($this->getGoogleRefreshTokenFilePath()));
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
                "access_type" => "offline");     

            $response = $this->httpClient->executeRequest(\HttpMethod::POST, "https://oauth2.googleapis.com/token", 
                array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["access_token"])) {
                throw new \RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }

            return new GoogleAuthenticationResult($response["access_token"], $response["expires_in"]);
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
            file_put_contents($this->getGoogleRefreshTokenFilePath(), $response["refresh_token"]);
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

        private function getGoogleRefreshTokenFilePath() {
            return dirname(__FILE__) . "/../../config/google.txt";
        }
    }
?>