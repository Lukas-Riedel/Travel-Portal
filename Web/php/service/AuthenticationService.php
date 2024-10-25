<?php
    require_once(dirname(__FILE__) . "/../exception/AuthenticationException.php");

    class AuthenticationService {

        private $databaseProvider;

        public function __construct($databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function authenticateWithCredentials($username, $password) {
            global $configuration;

            $userRow = $this->databaseProvider
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
            global $configuration;
            
            $roles = explode(",", $this->databaseProvider
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