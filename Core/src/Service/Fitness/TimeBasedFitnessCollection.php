<?php

    namespace Core\Service\Fitness;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "TimeBasedFitnessCollection",
        type: "object",
        description: "A class representing a collection of fitness records at a specific timestamp",
        required: ["timestamp", "fitness"],
        properties: [
            new OA\Property(
                property: "timestamp",
                type: "integer",
                description: "The timestamp of the fitness records in epoch seconds",
                example: 1688563200
            ),
            new OA\Property(
                property: "fitness",
                description: "The fitness records for the given timestamp",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Fitness")
            )
        ]
    )]
    class TimeBasedFitnessCollection implements \JsonSerializable {   
             
        private readonly int $timestamp;
        private readonly array $fitness;

        public function __construct(int $timestamp, array $fitness) {
            $this->timestamp = $timestamp;
            $this->fitness = $fitness;
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }

        public function getFitness() : array {
            return $this->fitness;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>