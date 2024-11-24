<?php
    class AuthenticationResult implements JsonSerializable {        
        private $accessToken;
        private $roles;
        private $validity;

        public function __construct($accessToken, $roles, $validity) {
            $this->accessToken = $accessToken;
            $this->roles = $roles;
            $this->validity = $validity;
        }

        public function getAccessToken() : string {
            return $this->accessToken;
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