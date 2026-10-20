<?php
    namespace Common\Service\Authentication;

    class UserInfo implements \JsonSerializable {  
              
        private readonly string $userId;
        private readonly ?string $username;
        private readonly string $client;
        private readonly bool $impersonated;
        private readonly bool $allowlistedServiceAccount;

        public function __construct(string $userId, ?string $username, string $client, bool $impersonated, bool $allowlistedServiceAccount) {
            $this->userId = $userId;
            $this->username = $username;
            $this->client = $client;
            $this->impersonated = $impersonated;
            $this->allowlistedServiceAccount = $allowlistedServiceAccount;
        }

        public function getUserId() : string {
            return $this->userId;
        }

        public function getUsername() : ?string {
            return $this->username;
        }

        public function getClient() : string {
            return $this->client;
        }

        public function isImpersonated() : bool {
            return $this->impersonated;
        }

        public function isAllowlistedServiceAccount() : bool {
            return $this->allowlistedServiceAccount;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>