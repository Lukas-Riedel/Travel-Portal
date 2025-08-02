<?php
    namespace Service\Service\Authentication;

    class AccessToken implements \JsonSerializable {        
        private readonly string $userId;
        private readonly array $roles;
        private readonly int $expiration;

        public function __construct(string $userId, array $roles, int $expiration) {
            $this->userId = $userId;
            $this->roles = $roles;
            $this->expiration = $expiration;
        }

        public function getUserId() : string {
            return $this->userId;
        }

        public function getRoles() : array {
            return $this->roles;
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