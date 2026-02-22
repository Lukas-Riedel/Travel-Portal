<?php
    namespace Core\Service\Forecast;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Interval",
        type: "object",
        description: "A class representing an interval",
        required: ["start", "end"],
        properties: [
            new OA\Property(
                property: "start",
                description: "The start of the interval",
                type: "number",
                format: "float",
                example: 13.7
            ),
            new OA\Property(
                property: "end",
                description: "The end of the interval",
                type: "number",
                format: "float",
                example: 39.4
            )
        ]
    )]
    class Interval implements \JsonSerializable {
        private readonly float $start;
        private readonly float $end;

        public function __construct(float $start, float $end) {
            $this->start = $start;
            $this->end = $end;
        }

        public function getStart() : float {
            return $this->start;
        }

        public function getEnd() : float {
            return $this->end;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>