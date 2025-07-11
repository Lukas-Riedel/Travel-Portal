<?php
    namespace Service\Service\Device;

    enum DeviceType : string {
        case Portal = "PORTAL";
        case Agent = "AGENT";
        case BridgeX = "BRIDGEX";
        
        public static function values() : array {
            return array_map(fn($case) => $case->value, self::cases());
        }
        
        public static function fromName(string $name) : ?DeviceType {
            foreach (DeviceType::cases() as $case) {
                if ($case->name === $name) {
                    return $case;
                }
            }
            return NULL;
        }
    }
?>