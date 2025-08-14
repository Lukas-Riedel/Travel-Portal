<?php
    namespace Core\Service\Device;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Device",
        type: "object",
        description: "A class representing a device",
        required: ["type", "token", "userId"],
        properties: [
            new OA\Property(
                property: "type",
                description: "The type of the device",
                ref: "#/components/schemas/DeviceType"
            ),
            new OA\Property(
                property: "token",
                description: "The token of the device",
                type: "string",
                example: "devjFpQfdQ32P6cG0X6DrY:APA9332t1acBH11y41gABcDiMuK2HsEOzDbI5Mh1vGBn-1Da6TggFUQb28KlIWDHRAFDCmmhFv7XHDvWTZFihX6bOCDcUQCzIFxa9vFGKKcVJsc"
            ),
            new OA\Property(
                property: "userId",
                description: "The identifier of the user owning the device",
                type: "string",
                example: "8c742eaf-a238-47cd-8d23-6704edf2cc58"
            ),
        ]
    )]
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