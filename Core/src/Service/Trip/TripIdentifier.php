<?php
    namespace Core\Service\Trip;
    
    use Core\Service\Highlight\Highlight;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "TripIdentifier",
        type: "object",
        description: "A class representing a trip identifier",
        required: ["id", "name"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The identifier of the trip",
                type: "string",
                example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
            ),
            new OA\Property(
                property: "name",
                description: "The name of the trip",
                type: "string",
                example: "One Thousand Scents of Sri Lanka"
            ),
            new OA\Property(
                property: "year",
                description: "The year of the trip",
                type: "integer",
                example: 2025
            ),
            new OA\Property(
                property: "mainHighlight",
                description: "The main highlight of the trip",
                ref: "#/components/schemas/Highlight"
            )
        ]
    )]
    class TripIdentifier implements \JsonSerializable {
        
        private const FULL_TRIP_NAME_FORMAT = "%s %d";
             
        private ?string $id;
        private readonly string $name;
        private readonly ?int $year;
        private readonly ?Highlight $mainHighlight;

        public function __construct(?string $id, string $name, ?int $year, ?Highlight $mainHighlight) {
            $this->id = $id;
            $this->name = $name;
            $this->year = $year;
            $this->mainHighlight = $mainHighlight;
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

        public function getFullName() : string {
            return sprintf(self::FULL_TRIP_NAME_FORMAT, $this->name, $this->year);
        }

        public function getYear() : ?int {
            return $this->year;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>