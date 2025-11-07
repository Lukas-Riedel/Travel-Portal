<?php
    namespace Core\Service\Year;

    use Core\Service\Highlight\Highlight;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Year",
        type: "object",
        description: "A class representing a year",
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
            ),
            new OA\Property(
                property: "fitness",
                description: "The day fitness records of the year",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Fitness")
            ),
            new OA\Property(
                property: "highlights",
                description: "The highlights of the year",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Highlight")
            ),
            new OA\Property(
                property: "statistics",
                description: "The statistics of the year",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Statistics")
            )
        ]
    )]
    class Year implements \JsonSerializable {        
        private readonly string $id;
        private readonly ?Highlight $mainHighlight;
        private readonly array $fitness;
        private readonly array $highlights;
        private readonly array $statistics;

        public function __construct(string $id, ?Highlight $mainHighlight, array $fitness, array $highlights, array $statistics) {
            $this->id = $id;
            $this->fitness = $fitness;
            $this->highlights = $highlights;
            $this->mainHighlight = $mainHighlight;
            $this->statistics = $statistics;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        public function getFitness() : array {
            return $this->fitness;
        }

        public function getHighlights() : array {
            return $this->highlights;
        }

        public function getStats() : array {
            return $this->statistics;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>