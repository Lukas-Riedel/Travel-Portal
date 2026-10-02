<?php
    namespace Core\Service\Geocoding;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Address",
        type: "object",
        description: "A class representing an address",
        required: ["type", "name", "address"],
        properties: [
            new OA\Property(
                property: "type",
                description: "The type of the address",
                ref: "#/components/schemas/AddressType"
            ),
            new OA\Property(
                property: "name",
                type: "string",
                description: "The human-readable name of the location (e.g., hotel or airport)",
                example: "Hotel Taggat"
            ),
            new OA\Property(
                property: "address",
                type: "string",
                description: "The string representation of the address",
                example: "108 Rue Vendôme, 69006 Lyon, France"
            )
        ]
    )]
    class Address implements \JsonSerializable {
            
        private readonly AddressType $type;
        private readonly string $name;
        private readonly string $address;

        public function __construct(AddressType $type, string $name, string $address) {
            $this->type = $type;
            $this->name = $name;
            $this->address = $address;
        }

        public function getType() : AddressType {
            return $this->type;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getAddress() : string {
            return $this->address;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>