<?php
    namespace Core\Event;

    class CloudMessagingEvent extends Event {
        private readonly array $requiredRoles;
        private readonly array $supportedDeviceTypes;

        public function __construct(string $name, array $requiredRoles, array $supportedDeviceTypes, array $args) {
            parent::__construct($name, $args);
            $this->requiredRoles = $requiredRoles;
            $this->supportedDeviceTypes = $supportedDeviceTypes;
        }
        
        public function getRequiredRoles() : array {
            return $this->requiredRoles;
        }

        public function getSupportedDeviceTypes() : array {
            return $this->supportedDeviceTypes;
        }
    }
?>