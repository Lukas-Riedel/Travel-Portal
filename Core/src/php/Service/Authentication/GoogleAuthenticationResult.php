<?php
    namespace Core\Service\Authentication;

    class GoogleAuthenticationResult {
        private readonly string $accessToken;
        private readonly int $expiresIn;

        public function __construct(string $accessToken, int $expiresIn) {
            $this->accessToken = $accessToken;
            $this->expiresIn = $expiresIn;
        }

        public function getAccessToken() : string {
            return $this->accessToken;
        }

        public function getExpiresIn() : int {
            return $this->expiresIn;
        }
    }