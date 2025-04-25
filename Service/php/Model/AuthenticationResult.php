<?php
    class AuthenticationResult implements JsonSerializable {        
        private $accessToken;
        private $refreshToken;
        private $roles;
        private $validity;

        public function __construct($accessToken, $refreshToken, $roles, $validity) {
            $this->accessToken = $accessToken;
            $this->refreshToken = $refreshToken;
            $this->roles = $roles;
            $this->validity = $validity;
        }

        public function getAccessToken() : string {
            return $this->accessToken;
        }

        public function getRefreshToken() : string {
            return $this->refreshToken;
        }

        public function getRoles() : array {
            return $this->roles;
        }

        public function getValidity() : int {
            return $this->validity;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>