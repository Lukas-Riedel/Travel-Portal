<?php
    class AccessToken implements JsonSerializable {        
        private $roles;
        private $version;
        private $expiration;

        public function __construct($roles, $version, $expiration) {
            $this->roles = $roles;
            $this->version = $version;
            $this->expiration = $expiration;
        }

        public function getRoles() : array {
            return $this->roles;
        }

        public function getVersion() : string {
            return $this->version;
        }

        public function getExpiration() : int {
            return $this->expiration;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>