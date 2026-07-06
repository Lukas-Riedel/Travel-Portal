<?php
    namespace Core\Service\Highlight;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "HighlightAttributes",
        type: "object",
        description: "A class representing highlight quality attributes",
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
            ),
            new OA\Property(
                property: "impression",
                description: "The impression score of the highlight",
                type: "integer",
                format: "int32",
                example: 90
            )
        ]
    )]
    class HighlightAttributes implements \JsonSerializable {
        
        private readonly ?int $composition;
        private readonly ?int $sky;
        private readonly ?int $shadows;
        private readonly ?int $circumstances;
        private readonly ?int $atmosphere;
        private readonly ?int $impression;

        public function __construct(?int $composition, ?int $sky, ?int $shadows, ?int $circumstances, ?int $atmosphere, ?int $impression) {
            $this->composition = $composition;
            $this->sky = $sky;
            $this->shadows = $shadows;
            $this->circumstances = $circumstances;
            $this->atmosphere = $atmosphere;
            $this->impression = $impression;
        }

        public function getComposition() : ?int {
            return $this->composition;
        }

        public function getSky() : ?int {
            return $this->sky;
        }

        public function getShadows() : ?int {
            return $this->shadows;
        }

        public function getCircumstances() : ?int {
            return $this->circumstances;
        }

        public function getAtmosphere() : ?int {
            return $this->atmosphere;
        }

        public function getImpression() : ?int {
            return $this->impression;
        }

        public function getQuality() : float {
            $values = array_filter(array($this->composition, $this->sky, $this->shadows, $this->circumstances, $this->atmosphere, $this->impression), fn($value) => !is_null($value));
            return in_array(0, $values, true) ? 0.0 : count($values) / array_sum(array_map(fn($value) => 1 / $value, $values));
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>