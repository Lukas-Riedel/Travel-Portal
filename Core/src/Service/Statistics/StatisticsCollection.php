<?php
    namespace Core\Service\Statistics;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "StatisticsCollection",
        type: "object",
        description: "A class representing a collection of statistics records",
        required: ["statistics", "timestamp"],
        properties: [
            new OA\Property(
                property: "statistics",
                description: "The statistics records",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Statistics")
            ),
            new OA\Property(
                property: "start",
                description: "The timestamp when the statistics records were created in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1688563200
            ),
        ]
    )]
    class StatisticsCollection implements \JsonSerializable {
        
        private readonly array $statistics;
        private readonly int $timestamp;

        public function __construct(array $statistics, int $timestamp) {
            $this->statistics = $statistics;
            $this->timestamp = $timestamp;
        }

        public function getStatistics() : array {
            return $this->statistics;
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>