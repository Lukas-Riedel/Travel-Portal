<?php
    namespace Core\Service\Highlight;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "HighlightAttributes",
        type: "object",
        description: "A class representing highlight quality attributes",
        required: [],
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
        private readonly ?int $composition;
        private readonly ?int $sky;
        private readonly ?int $shadows;
        private readonly ?int $circumstances;
        private readonly ?int $atmosphere;

        public function __construct(?int $composition, ?int $sky, ?int $shadows, ?int $circumstances, ?int $atmosphere) {
            $this->composition = $composition;
            $this->sky = $sky;
            $this->shadows = $shadows;
            $this->circumstances = $circumstances;
            $this->atmosphere = $atmosphere;
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

        public function getQuality() : ?float {
            if ($this->composition === null || $this->sky === null || $this->shadows === null || $this->circumstances === null || $this->atmosphere === null
                || $this->composition === 0 || $this->sky === 0 || $this->shadows === 0 || $this->circumstances === 0 || $this->atmosphere === 0) {
                return null;
            }

            return 5.0 / (1 / $this->composition + 1 / $this->sky + 1 / $this->shadows + 1 / $this->circumstances + 1 / $this->atmosphere);
        }


        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>