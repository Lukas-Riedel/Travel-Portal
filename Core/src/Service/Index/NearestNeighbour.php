<?php
    namespace Core\Service\Index;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "NearestNeighbour",
        type: "object",
        description: "A class representing a nearest neighbour",
        required: ["entityId", "score"],
        properties: [
            new OA\Property(
                property: "entityId",
                type: "string",
                description: "The identifier of the neighbour",
                example: "c799fd70-cbc1-4624-992f-a3c740706f8a"
            ),
            new OA\Property(
                property: "score",
                type: "number",
                format: "float",
                description: "The similarity to the neighbour",
                example: 0.75431
            )
        ]
    )]
    class NearestNeighbour implements \JsonSerializable {
        private readonly string $entityId;
        private readonly float $score;

        public function __construct(string $entityId, float $score) {
            $this->entityId = $entityId;
            $this->score = $score;
        }

        public function getEntityId() : string {
            return $this->entityId;
        }

        public function getScore() : float {
            return $this->score;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>