<?php
    namespace Core\Service\Device;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Device",
        type: "object",
        description: "A class representing a device",
        required: ["id", "type", "name", "lastSeen"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The device-generated identifier of the device",
                type: "string",
                example: "8f3b0c9a-5cfa-4d47-bf5e-8e8f9f3a1a2b"
            ),
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
                property: "data",
                description: "The data of the device",
                type: "object",
                additionalProperties: true,
                example: [
                    "fcmToken" => "fcm-1234567890"
                ]
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
        private readonly string $id;
        private readonly DeviceType $type;
        private readonly string $name;
        private readonly mixed $data;
        private readonly string $userId;
        private readonly int $lastSeen;

        public function __construct(string $id, DeviceType $type, string $name, mixed $data, string $userId, int $lastSeen) {
            $this->id = $id;
            $this->type = $type;
            $this->name = $name;
            $this->data = $data;
            $this->userId = $userId;
            $this->lastSeen = $lastSeen;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getType() : DeviceType {
            return $this->type;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getData() : mixed {
            return $this->data;
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