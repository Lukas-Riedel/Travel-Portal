<?php
    namespace Core\Service\Year;

    use Core\Service\Highlight\Highlight;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "YearIdentifier",
        type: "object",
        description: "A class representing a year identifier",
        required: ["id"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The identifier of the year",
                type: "integer",
                example: "2025"
            ),
            new OA\Property(
                property: "mainHighlight",
                description: "The main highlight of the year",
                ref: "#/components/schemas/Highlight"
            )
        ]
    )]
    class YearIdentifier implements \JsonSerializable {        
        private int $id;
        private readonly ?Highlight $mainHighlight;

        public function __construct(int $id, ?Highlight $mainHighlight) {
            $this->id = $id;
            $this->mainHighlight = $mainHighlight;
        }

        public function getId() : int {
            return $this->id;
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