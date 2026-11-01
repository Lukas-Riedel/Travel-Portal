<?php
    namespace Core\Service\CarRental;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "CarRental",
        type: "object",
        description: "A class representing a car rental",
        required: ["model", "start", "end"],
        properties: [
            new OA\Property(
                property: "model",
                description: "The model of the rented car",
                type: "string",
                example: "Škoda Octavia"
            ),
            new OA\Property(
                property: "start",
                description: "The start time of the car rental in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1688563200
            ),
            new OA\Property(
                property: "end",
                description: "The end time of the car rental in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            )
        ]
    )]
    class CarRental implements \JsonSerializable {
        
        private readonly string $model;
        private readonly int $start;
        private readonly int $end;

        public function __construct(string $model, int $start, int $end) {
            $this->model = $model;
            $this->start = $start;
            $this->end = $end;
        }

        public function getModel() : string {
            return $this->model;
        }

        public function getStart() : int {
            return $this->start;
        }

        public function getEnd() : int {
            return $this->end;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
