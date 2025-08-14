<?php
    namespace Core\Service\Authentication;

    class AuthenticationResult implements \JsonSerializable {        
        private readonly string $accessToken;
        private readonly string $refreshToken;
        private readonly array $roles;
        private readonly int $validity;

        public function __construct(string $accessToken, string $refreshToken, array $roles, int $validity) {
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