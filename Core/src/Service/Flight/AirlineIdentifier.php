<?php
    namespace Core\Service\Flight;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "AirlineIdentifier",
        type: "object",
        description: "A class representing an airline identifier",
        required: ["id", "name"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The system-generated identifier of the airline",
                type: "string",
                example: "5da888c6-4a73-4424-bc28-ee78abef5796"
            ),
            new OA\Property(
                property: "name",
                description: "The name of the airline",
                type: "string",
                example: "WizzAir"
            )
        ]
    )]
    class AirlineIdentifier implements \JsonSerializable { 
               
        private readonly string $id;
        private readonly string $name;

        public function __construct(string $id, string $name) {
            $this->id = $id;
            $this->name = $name;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>