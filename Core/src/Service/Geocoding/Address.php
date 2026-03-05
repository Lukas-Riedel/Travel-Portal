<?php
    namespace Core\Service\Geocoding;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Address",
        type: "object",
        description: "A class representing an address",
        required: ["address"],
        properties: [
            new OA\Property(
                property: "address",
                type: "string",
                description: "The string representation of the address",
                example: "108 Rue Vendôme, 69006 Lyon, France"
            )
        ]
    )]
    class Address implements \JsonSerializable {    
            
        private readonly string $address;

        public function __construct(string $address) {
            $this->address = $address;
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