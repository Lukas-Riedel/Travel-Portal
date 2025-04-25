<?php
    namespace Service\Service\Authentication;

    class User implements \JsonSerializable {
        private readonly string $username;
        private readonly string $password;
        private readonly array $roles;

        public function __construct(string $username, string $password, array $roles) {
            $this->username = $username;
            $this->password = $password;
            $this->roles = $roles;
        }

        public function getUsername() : string {
            return $this->username;
        }

        public function getPassword() : string {
            return $this->password;
        }

        public function getRoles() : array {
            return $this->roles;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>