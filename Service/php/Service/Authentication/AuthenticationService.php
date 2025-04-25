<?php
    namespace Service\Service\Authentication;

    class AuthenticationService {

        private const DELIMITER = "::";
        private const HASH_ALGORITHM = "sha256";
        private const ACCESS_TOKEN = "ACCESS_TOKEN";
        private const REFRESH_TOKEN = "REFRESH_TOKEN";

        private readonly AuthenticationMapper $authenticationMapper;

        private readonly \ConfigurationService $configurationService;

        public function __construct(\DatabaseProvider $databaseProvider, \ConfigurationService $configurationService) {
            $this->authenticationMapper = new AuthenticationMapper($databaseProvider);
            $this->configurationService = $configurationService;
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
            $decrypted = openssl_decrypt($encryptedData, $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "cipher"), $this->getAccessTokenPrivatekey(), 0, $iv);
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

            if ($decodedAccessToken["version"] !== $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "version")) {
                throw new AuthenticationException("The access token version " . $decodedAccessToken["version"] . " is outdated.");
            }

            return new AccessToken($decodedAccessToken["roles"], $decodedAccessToken["version"], $decodedAccessToken["expiration"]);
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
            $decrypted = openssl_decrypt($encryptedData, $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "cipher"), $this->getRefreshTokenPrivatekey(), 0, $iv);
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

            if ($decodedRefreshToken["version"] !== $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "version")) {
                throw new AuthenticationException("The refresh token version " . $decodedRefreshToken["version"] . " is outdated.");
            }

            return $this->generateAuthenticationResult($decodedRefreshToken["roles"], $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "validity"));
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

            return $this->generateAuthenticationResult($user->getRoles(), $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "validity"));
        }

        public function authenticateWithApiKey(string $apiKey) : AuthenticationResult {
            $user = $this->authenticationMapper->selectUserByApiKey($apiKey);

            if ($user === NULL) {
                throw new AuthenticationException("No user for the provided API key was found.");
            }

            return $this->generateAuthenticationResult($user->getRoles(), $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "validity"));
        }

        public function authenticateAsAdmin(int $validity) : AuthenticationResult {
            return $this->generateAuthenticationResult(array("ADMIN", "USER"), $validity);
        }

        private function generateAuthenticationResult(array $roles, int $validity) : AuthenticationResult {
            $rawAccessToken = new AccessToken($roles, $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "version"), time() + $validity);
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->configurationService->getConfigurationForTypeAndKey("bearerToken", "cipher")));
            $encrypted = openssl_encrypt(json_encode($rawAccessToken), $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "cipher"), $this->getAccessTokenPrivatekey(), 0, $iv);
            $accessToken = base64_encode($encrypted . self::DELIMITER . $iv);
            
            $rawRefreshToken = new AccessToken($roles, $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "version"), time() + 12 * $validity);
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->configurationService->getConfigurationForTypeAndKey("bearerToken", "cipher")));
            $encrypted = openssl_encrypt(json_encode($rawRefreshToken), $this->configurationService->getConfigurationForTypeAndKey("bearerToken", "cipher"), $this->getRefreshTokenPrivatekey(), 0, $iv);
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