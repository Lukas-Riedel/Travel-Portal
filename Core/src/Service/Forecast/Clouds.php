<?php
    namespace Core\Service\Forecast;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Clouds",
        type: "object",
        description: "A class representing a cloud forecast",
        required: ["total"],
        properties: [
            new OA\Property(
                property: "total",
                description: "The total cloud coverage percentage",
                type: "number",
                format: "float",
                example: 25.6
            ),
            new OA\Property(
                property: "low",
                description: "The total low cloud coverage percentage",
                type: "number",
                format: "float",
                example: 13.2
            ),
            new OA\Property(
                property: "medium",
                description: "The total medium cloud coverage percentage",
                type: "number",
                format: "float",
                example: 11.8
             ),
             new OA\Property(
                property: "high",
                description: "The total high cloud coverage percentage",
                type: "number",
                format: "float",
                example: 0.0
             )
        ]
    )]
    class Clouds implements \JsonSerializable {

        private readonly float $total;
        private readonly ?float $low;
        private readonly ?float $medium;
        private readonly ?float $high;

        public function __construct(float $total, ?float $low, ?float $medium, ?float $high) {
            $this->total = $total;
            $this->low = $low;
            $this->medium = $medium;
            $this->high = $high;
        }

        public function getTotal() : float {
            return $this->total;
        }

        public function getLow() : ?float {
            return $this->low;
        }

        public function getMedium() : ?float {
            return $this->medium;
        }

        public function getHigh() : ?float {
            return $this->high;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>