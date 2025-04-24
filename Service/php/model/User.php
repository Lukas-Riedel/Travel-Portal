<?php
    class User implements JsonSerializable {
        private $username;
        private $password;
        private $roles;

        public function __construct($username, $password, $roles) {
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