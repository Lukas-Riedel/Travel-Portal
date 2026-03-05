<?php
    namespace Core\Service\Highlight;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "HighlightAttributes",
        type: "object",
        description: "A class representing highlight quality attributes",
        required: ["composition", "sky", "shadows", "circumstances", "atmosphere"],
        properties: [
            new OA\Property(
                property: "composition",
                description: "The composition score of the highlight",
                type: "integer",
                format: "int32",
                example: 100
            ),
            new OA\Property(
                property: "sky",
                description: "The sky score of the highlight",
                type: "integer",
                format: "int32",
                example: 90
            ),
            new OA\Property(
                property: "shadows",
                description: "The shadows score of the highlight",
                type: "integer",
                format: "int32",
                example: 70
            ),
            new OA\Property(
                property: "circumstances",
                description: "The circumstances score of the highlight",
                type: "integer",
                format: "int32",
                example: 100
            ),
            new OA\Property(
                property: "atmosphere",
                description: "The atmosphere score of the highlight",
                type: "integer",
                format: "int32",
                example: 90
            )
        ]
    )]
    class HighlightAttributes implements \JsonSerializable {
        
        private readonly int $composition;
        private readonly int $sky;
        private readonly int $shadows;
        private readonly int $circumstances;
        private readonly int $atmosphere;

        public function __construct(int $composition, int $sky, int $shadows, int $circumstances, int $atmosphere) {
            $this->composition = $composition;
            $this->sky = $sky;
            $this->shadows = $shadows;
            $this->circumstances = $circumstances;
            $this->atmosphere = $atmosphere;
        }

        public function getComposition() : int {
            return $this->composition;
        }

        public function getSky() : int {
            return $this->sky;
        }

        public function getShadows() : int {
            return $this->shadows;
        }

        public function getCircumstances() : int {
            return $this->circumstances;
        }

        public function getAtmosphere() : int {
            return $this->atmosphere;
        }

        public function getQuality() : ?float {
            $values = array($this->composition, $this->sky, $this->shadows, $this->circumstances, $this->atmosphere);
            return in_array(0, $values, true) ? 0.0 : count($values) / array_sum(array_map(fn($value) => 1 / $value, $values));
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>