<?php
    namespace Core\Service\Authentication;

    // TODO: Swagger annotations
    class IamResponse implements \JsonSerializable {     
        
        private readonly string $accessToken;
        private readonly int $expiresIn;
        private readonly string $refreshToken;
        private readonly int $refreshExpiresIn;

        public function __construct(string $accessToken, int $expiresIn, string $refreshToken, int $refreshExpiresIn) {
            $this->accessToken = $accessToken;
            $this->expiresIn = $expiresIn;
            $this->refreshToken = $refreshToken;
            $this->refreshExpiresIn = $refreshExpiresIn;
        }

        public function getAccessToken() : string {
            return $this->accessToken;
        }

        public function getExpiresIn() : int {
            return $this->expiresIn;
        }

        public function getRefreshToken() : string {
            return $this->refreshToken;
        }

        public function getRefreshExpiresIn() : int {
            return $this->refreshExpiresIn;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>