<?php
    namespace Core\Service\Lifestyle;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "LifestyleEvent",
        type: "object",
        required: ["id", "timestamp", "type", "content"],
        properties: [
            new OA\Property(
                property: "id", 
                type: "string",
                description: "The unique identifier of the lifestyle event",
                example: "c799fd70-cbc1-4624-992f-a3c740706f8a"
            ),
            new OA\Property(
                property: "type",
                description: "The type of the lifestyle event",
                ref: "#/components/schemas/LifestyleEventType"
            ),
            new OA\Property(
                property: "timestamp",
                type: "integer",
                description: "The time of the lifestyle event in epoch seconds",
                example: 1753912800
            ),
            new OA\Property(
                property: "content",
                type: "string",
                description: "The content of the lifestyle event",
                example: "Big Mac Meal"
            ),
            new OA\Property(
                property: "calories",
                type: "number",
                format: "float",
                description: "The calories of the lifestyle event",
                example: 1250.8
            )
        ]
    )]
    class LifestyleEvent implements \JsonSerializable {

        private ?string $id;
        private readonly LifestyleEventType $type;
        private readonly int $timestamp;
        private readonly string $content;
        private readonly ?float $calories;

        public function __construct(?string $id,LifestyleEventType $type, int $timestamp, string $content, ?float $calories) {
            $this->timestamp = $timestamp;
            $this->id = $id;
            $this->type = $type;
            $this->content = $content;
            $this->calories = $calories;
        }

        public function getId() : ?string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getType() : LifestyleEventType {
            return $this->type;
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }

        public function getContent() : ?string {
            return $this->content;
        }

        public function getCalories() : ?float {
            return $this->calories;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
