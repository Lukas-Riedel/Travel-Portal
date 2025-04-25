<?php
    namespace Service\Service\Authentication;

    class AccessToken implements \JsonSerializable {        
        private readonly array $roles;
        private readonly string $version;
        private readonly int $expiration;

        public function __construct(array $roles, string $version, int $expiration) {
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