<?php

    namespace Core\Service\Fitness;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "TimeBasedFitness",
        type: "object",
        description: "A class representing fitness data at a specific timestamp",
        required: ["timestamp", "fitness"],
        properties: [
            new OA\Property(
                property: "timestamp",
                type: "integer",
                description: "The timestamp of the fitness record in epoch seconds",
                example: 1688563200
            ),
            new OA\Property(
                property: "fitness",
                ref: "#/components/schemas/Fitness",
                description: "The fitness record for the given timestamp"
            )
        ]
    )]
    class TimeBasedFitness implements \JsonSerializable {   
             
        private readonly int $timestamp;
        private readonly Fitness $fitness;

        public function __construct(int $timestamp, int $steps, int $seconds, float $distance) {
            $this->timestamp = $timestamp;
            $this->fitness = new Fitness($steps, $seconds, $distance);
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }

        public function getFitness() : Fitness {
            return $this->fitness;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>