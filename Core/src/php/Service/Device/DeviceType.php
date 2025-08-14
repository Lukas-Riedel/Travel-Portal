<?php
    namespace Core\Service\Device;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "DeviceType",
        type: "string",
        description: "An enum representing a device type"
    )]
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