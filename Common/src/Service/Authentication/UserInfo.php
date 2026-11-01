<?php
    namespace Common\Service\Authentication;

    class UserInfo implements \JsonSerializable {  
              
        private readonly string $userId;
        private readonly ?string $username;
        private readonly string $client;
        private readonly bool $trustedServiceAccount;

        public function __construct(string $userId, ?string $username, string $client, bool $trustedServiceAccount) {
            $this->userId = $userId;
            $this->username = $username;
            $this->client = $client;
            $this->trustedServiceAccount = $trustedServiceAccount;
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

        public function isTrustedServiceAccount() : bool {
            return $this->trustedServiceAccount;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>