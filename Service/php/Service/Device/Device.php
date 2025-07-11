<?php
    namespace Service\Service\Device;

    class Device {

        private readonly DeviceType $type;
        private readonly string $token;

        public function __construct(DeviceType $type, string $token) {
            $this->type = $type;
            $this->token = $token;
        }

        public function getType() : DeviceType {
            return $this->type;
        }

        public function getToken() : string {
            return $this->token;
        }
    }
?>