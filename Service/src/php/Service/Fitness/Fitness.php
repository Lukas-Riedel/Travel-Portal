<?php

    namespace Service\Service\Fitness;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Fitness",
        type: "object",
        description: "A class representing a fitness record",
        required: ["steps", "seconds", "calories", "distance"],
        properties: [
            new OA\Property(
                property: "steps",
                type: "integer",
                description: "The number of steps taken within the record",
                example: 8742
            ),
            new OA\Property(
                property: "seconds",
                type: "integer",
                description: "The duration of the activity in seconds within the record",
                example: 3721
            ),
            new OA\Property(
                property: "calories",
                type: "number",
                format: "float",
                description: "The number of calories burned within the record",
                example: 321.5
            ),
            new OA\Property(
                property: "distance",
                type: "number",
                format: "float",
                description: "The distance covered in kilometers within the record",
                example: 6.4
            )
        ]
    )]
    class Fitness implements \JsonSerializable {        
        private readonly int $steps;
        private readonly int $seconds;
        private readonly float $calories;
        private readonly float $distance;

        public function __construct(int $steps, int $seconds, float $calories, float $distance) {
            $this->steps = $steps;
            $this->seconds = $seconds;
            $this->calories = $calories;
            $this->distance = $distance;
        }

        public function getSteps() : int {
            return $this->steps;
        }

        public function getSeconds() : int {
            return $this->seconds;
        }

        public function getCalories() : float {
            return $this->calories;
        }

        public function getDistance() : float {
            return $this->distance;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>