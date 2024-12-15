<?php
    require_once(dirname(__FILE__) . "/../model/AccessToken.php");
    require_once(dirname(__FILE__) . "/../model/AuthenticationResult.php");
    require_once(dirname(__FILE__) . "/../exception/AuthenticationException.php");
    require_once(dirname(__FILE__) . "/../config/secrets.php");

    class AuthenticationService {
        public function getAccessToken($accessToken) : AccessToken {
            global $configuration;

            if ($accessToken === NULL) {
                throw new AuthenticationException("The access token was not provided.");
            }

            $decoded = base64_decode($accessToken);
            if ($decoded === FALSE) {
                throw new AuthenticationException("The access token could not be read.");
            }
    
            $parts = explode('::', $decoded, 2);
            if (count($parts) !== 2) {
                throw new AuthenticationException("The access token could not be read.");
            }
            
            list($encryptedData, $iv) = $parts;
            $decrypted = openssl_decrypt($encryptedData, $configuration["bearerToken"]["cipher"], PRIVATE_KEY, 0, $iv);
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

            if ($decodedAccessToken["version"] !== $configuration["bearerToken"]["version"]) {
                throw new AuthenticationException("The access token version " . $decodedAccessToken["version"] . " is outdated.");
            }

            return new AccessToken($decodedAccessToken["roles"], $decodedAccessToken["version"], $decodedAccessToken["expiration"]);
        }

        public function authenticateWithCredentials($username, $password) : AuthenticationResult {
            global $configuration, $databaseProvider;

            $userRow = $databaseProvider
                ->statementBuilder("SELECT * FROM users WHERE username = ?")
                ->withParameters($username)
                ->getSingleRow();

            if ($userRow == NULL) {
                throw new AuthenticationException("The user '" . $username . "' was not found.");
            }

            if ($userRow["password"] == NULL) {
                // Set the password on the first call of the IAM endpoint for the specified user.
                // Sufficient for now, create a separate service for users if needed.
                $databaseProvider
                    ->statementBuilder("UPDATE users SET password = ? WHERE username = ?")
                    ->withParameters(password_hash($password, PASSWORD_DEFAULT), $username)
                    ->execute();
            }
            else if (!password_verify($password, $userRow["password"])) {
                throw new AuthenticationException("Password for the user '" . $username . "' is invalid.");
            }

            $roles = explode(",", $userRow["roles"]);
            return $this->generateAuthenticationResult($roles, $configuration["bearerToken"]["validity"]);
        }

        public function authenticateWithApiKey($apiKey) : AuthenticationResult {
            global $configuration, $databaseProvider;
            
            $roles = explode(",", $databaseProvider
                ->statementBuilder("SELECT roles FROM users WHERE api_key = ?")
                ->withParameters($apiKey)
                ->getSingleColumn("roles"));

            if ($roles == NULL) {
                throw new AuthenticationException("No user for the provided API key was found.");
            }

            return $this->generateAuthenticationResult($roles, $configuration["bearerToken"]["validity"]);
        }

        public function authenticateAsAdmin($validity) : AuthenticationResult {
            return $this->generateAuthenticationResult(array("ADMIN", "USER"), $validity);
        }

        private function generateAuthenticationResult($roles, $validity) : AuthenticationResult {
            global $configuration;

            $rawAccessToken = new AccessToken($roles, $configuration["bearerToken"]["version"], time() + $validity);
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($configuration["bearerToken"]["cipher"]));
            $encrypted = openssl_encrypt(json_encode($rawAccessToken), $configuration["bearerToken"]["cipher"], PRIVATE_KEY, 0, $iv);
            $accessToken = base64_encode($encrypted . '::' . $iv);

            return new AuthenticationResult($accessToken, $roles, $validity);
        }
    }
?>