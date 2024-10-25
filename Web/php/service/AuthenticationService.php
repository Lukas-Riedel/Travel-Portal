<?php
    require_once(dirname(__FILE__) . "/../exception/AuthenticationException.php");

    class AuthenticationService {
        public function getAccessToken($accessToken) {
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
            $decrypted = openssl_decrypt($encryptedData, $configuration["bearerToken"]["cipher"], $configuration["bearerToken"]["privateKey"], 0, $iv);
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
                setcookie("accessToken", "", time() - 3600, "/");
                setcookie("roles", "", time() - 3600, "/");
                throw new AuthenticationException("The access token version " . $decodedAccessToken["version"] . " is outdated.");
            }

            return $decodedAccessToken;
        }

        public function authenticateWithCredentials($username, $password) {
            global $configuration, $databaseProvider;

            $userRow = $databaseProvider
                ->statementBuilder("SELECT * FROM users WHERE username = ?")
                ->withParameters($username)
                ->getSingleRow();

            if ($userRow == NULL) {
                throw new AuthenticationException("The user '" . $username . "' was not found.");
            }

            if (!password_verify($password, $userRow["password"])) {
                throw new AuthenticationException("Passowrd for the user '" . $username . "' is invalid.");
            }

            $roles = explode(",", $userRow["roles"]);
            return $this->generateAuthenticationResult($roles, $configuration["bearerToken"]["validity"]);
        }

        public function authenticateWithApiKey($apiKey) {
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

        public function authenticateAsAdmin($validity) {
            return $this->generateAuthenticationResult(array("ADMIN", "USER"), $validity);
        }

        private function generateAuthenticationResult($roles, $validity) {
            global $configuration;

            $result = array(
                "roles" => $roles,
                "version" => $configuration["bearerToken"]["version"],
                "expiration" => time() + $validity);

            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($configuration["bearerToken"]["cipher"]));
            $encrypted = openssl_encrypt(json_encode($result), $configuration["bearerToken"]["cipher"], $configuration["bearerToken"]["privateKey"], 0, $iv);
            $accessToken = base64_encode($encrypted . '::' . $iv);

            return array(
                "accessToken" => $accessToken,
                "roles" => $roles,
                "validity" => $validity);
        }
    }
?>