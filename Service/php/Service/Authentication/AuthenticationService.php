<?php
    namespace Service\Service\Authentication;

    class AuthenticationService {

        private const REFRESH_TOKEN_VALIDITY_MULTIPLIER = 24;
        private const BEARER_TOKEN_VALIDITY = 3600;
        private const ADMIN_USER_ID = "999";

        private const DELIMITER = "::";
        private const HASH_ALGORITHM = "sha256";
        private const CIPHER_ALGORITHM = "aes-256-cbc";
        private const ACCESS_TOKEN = "ACCESS_TOKEN";
        private const REFRESH_TOKEN = "REFRESH_TOKEN";

        private readonly AuthenticationMapper $authenticationMapper;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->authenticationMapper = new AuthenticationMapper($databaseProvider);
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