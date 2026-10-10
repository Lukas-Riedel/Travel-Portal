<?php
    namespace Common\Service\Authentication;

    class UserInfo implements \JsonSerializable {  
              
        private readonly string $userId;
        private readonly string $client;

        public function __construct(string $userId, string $client) {
            $this->userId = $userId;
            $this->client = $client;
        }

        public function getUserId() : string {
            return $this->userId;
        }

        public function getClient() : string {
            return $this->client;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>