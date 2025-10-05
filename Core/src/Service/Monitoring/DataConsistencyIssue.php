<?php
    namespace Core\Service\Monitoring;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "DataConsistencyIssue",
        type: "object",
        description: "A class representing a data consistency issue",
        required: ["name", "context", "timestamp"],
        properties: [
            new OA\Property(
                property: "name",
                type: "string",
                description: "The name of the data consistency issue",
                example: "EMPTY_ALBUM"
            ),
            new OA\Property(
                property: "context",
                description: "The context of the data consistency issue",
                oneOf: [
                    new OA\Schema(type: "object"),
                    new OA\Schema(type: "array", items: new OA\Items()),
                    new OA\Schema(type: "string")
                ]
            ),
            new OA\Property(
                property: "timestamp",
                type: "integer",
                description: "The timestamp of the data consistency issue",
                example: 1722819900
            )
        ]
    )]
    class DataConsistencyIssue implements \JsonSerializable {
        private readonly string $name;
        private readonly mixed $context;
        private readonly int $timestamp;

        public function __construct(string $name, mixed $context, int $timestamp) {
            $this->name = $name;
            $this->context = $context;
            $this->timestamp = $timestamp;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getContext() : mixed {
            return $this->context;
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