<?php
    namespace Service\Service\Device;

    class Device {

        private readonly DeviceType $type;
        private readonly string $token;
        private readonly string $userId;

        public function __construct(DeviceType $type, string $token, string $userId) {
            $this->type = $type;
            $this->token = $token;
            $this->userId = $userId;
        }

        public function getType() : DeviceType {
            return $this->type;
        }

        public function getToken() : string {
            return $this->token;
        }

        public function getUserId() : string {
            return $this->userId;
        }
    }
?>