<?php
    namespace Core\Service\Forecast;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Precipitation",
        type: "object",
        description: "A class representing a precipitation forecast",
        required: ["total"],
        properties: [
            new OA\Property(
                property: "total",
                description: "The total precipitation in millimeters",
                type: "number",
                format: "float",
                example: 0.3
            ),
            new OA\Property(
                property: "probability",
                description: "The probability of precipitation",
                type: "number",
                format: "float",
                example: 95
            ),
        ]
    )]
    class Precipitation implements \JsonSerializable {

        private readonly float $total;
        private readonly ?float $probability;

        public function __construct(float $total, ?float $probability) {
            $this->total = $total;
            $this->probability = $probability;
        }

        public function getTotal() : float {
            return $this->total;
        }
        public function getProbability() : ?float {
            return $this->probability;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>