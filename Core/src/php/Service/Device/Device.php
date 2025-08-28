<?php
    namespace Core\Service\Device;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Device",
        type: "object",
        description: "A class representing a device",
        required: ["type", "name", "token", "lastSeen"],
        properties: [
            new OA\Property(
                property: "type",
                description: "The type of the device",
                ref: "#/components/schemas/DeviceType"
            ),
            new OA\Property(
                property: "name",
                description: "The name of the device",
                type: "string",
                example: "DESKTOP-PC"
            ),
            new OA\Property(
                property: "token",
                description: "The token of the device",
                type: "string",
                example: "devjFpQfdQ32P6cG0X6DrY:APA9332t1acBH11y41gABcDiMuK2HsEOzDbI5Mh1vGBn-1Da6TggFUQb28KlIWDHRAFDCmmhFv7XHDvWTZFihX6bOCDcUQCzIFxa9vFGKKcVJsc"
            ),
            new OA\Property(
                property: "lastSeen",
                description: "The last seen time of the device in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1688563200
            )
        ]
    )]
    class Device implements \JsonSerializable {

        private readonly DeviceType $type;
        private readonly string $name;
        private readonly string $token;
        private readonly string $userId;
        private readonly int $lastSeen;

        public function __construct(DeviceType $type, string $name, string $token, string $userId, int $lastSeen) {
            $this->type = $type;
            $this->name = $name;
            $this->token = $token;
            $this->userId = $userId;
            $this->lastSeen = $lastSeen;
        }

        public function getType() : DeviceType {
            return $this->type;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getToken() : string {
            return $this->token;
        }

        public function getUserId() : string {
            return $this->userId;
        }

        public function getLastSeen() : int {
            return $this->lastSeen;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            $objectVars = get_object_vars($this);
            unset($objectVars["userId"]);
            return $objectVars;
        }
    }
?>