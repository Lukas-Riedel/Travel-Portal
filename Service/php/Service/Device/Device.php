<?php
    namespace Service\Service\Device;

    class Device {

        private readonly DeviceType $type;
        private readonly string $token;
        private readonly array $roles;

        public function __construct(DeviceType $type, string $token, array $roles) {
            $this->type = $type;
            $this->token = $token;
            $this->roles = $roles;
        }

        public function getType() : DeviceType {
            return $this->type;
        }

        public function getToken() : string {
            return $this->token;
        }

        public function getRoles() : array {
            return $this->roles;
        }
    }
?>