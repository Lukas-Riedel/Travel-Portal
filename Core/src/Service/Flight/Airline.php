<?php
    namespace Core\Service\Flight;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Airline",
        type: "object",
        description: "A class representing an airline",
        required: ["id", "name", "codes"],
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
            ),
            new OA\Property(
                property: "codes",
                description: "The IATA codes of the airline",
                type: "array",
                items: new OA\Items(type: "string"),
                example: ["W4", "W6", "W9"]
            ),
            new OA\Property(
                property: "logo",
                description: "The logo of the airline in SVG format",
                type: "string",
                example: "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"10\" height=\"10\"><circle cx=\"5\" cy=\"5\" r=\"5\" fill=\"black\"/></svg>"
            )
        ]
    )]
    class Airline implements \JsonSerializable {    
            
        private ?string $id;
        private readonly string $name;
        private readonly array $codes;
        private readonly ?string $logo;

        public function __construct(?string $id, string $name, array $codes, ?string $logo) {
            $this->id = $id;
            $this->name = $name;
            $this->codes = $codes;
            $this->logo = $logo;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCodes() : array {
            return $this->codes;
        }

        public function getLogo() : ?string {
            return $this->logo;
        }

        public function getAirlineIdentifier() : AirlineIdentifier {
            return new AirlineIdentifier($this->id, $this->name);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>