<?php
    namespace Core\Service\Geocoding;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Address",
        type: "object",
        description: "A class representing an address",
        required: ["address", "lastUpdate"],
        properties: [
            new OA\Property(
                property: "address",
                type: "string",
                description: "The string representation of the address",
                example: "108 Rue Vendôme, 69006 Lyon, France"
            ),
            new OA\Property(
                property: "lastUpdate",
                description: "The last update time of the address in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            )
        ]
    )]
    class Address implements \JsonSerializable {        
        private readonly string $address;
        private readonly int $lastUpdate;

        public function __construct(string $address, int $lastUpdate) {
            $this->address = $address;
            $this->lastUpdate = $lastUpdate;
        }

        public function getAddress() : string {
            return $this->address;
        }

        public function getLastUpdate() : int {
            return $this->lastUpdate;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>