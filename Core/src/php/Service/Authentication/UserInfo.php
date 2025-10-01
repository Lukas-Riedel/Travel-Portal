<?php
    namespace Core\Service\Authentication;

    // TODO: Swagger annotations
    class UserInfo implements \JsonSerializable {        
        private readonly string $userId;
        private readonly array $roles;

        public function __construct(string $userId, array $roles) {
            $this->userId = $userId;
            $this->roles = $roles;
        }

        public function getUserId() : string {
            return $this->userId;
        }

        public function getRoles() : array {
            return $this->roles;
        }

        public function isAdmin() : bool {
            return in_array("ADMIN", $this->roles);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>