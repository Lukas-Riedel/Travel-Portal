<?php
    namespace Core\Service\TimeTracking;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "TimeTrackingEvent",
        type: "object",
        description: "An object representing a time tracking event",
        required: ["id", "description", "hours", "timestamp", "type", "balance"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The unique identifier of the time tracking event",
                example: "c799fd70-cbc1-4624-992f-a3c740706f8a"
            ),
            new OA\Property(
                property: "description",
                type: "string",
                description: "The description of the time tracking event",
                example: "Integrating Redis to Scanner Worker"
            ),
            new OA\Property(
                property: "hours",
                description: "The hours of the time tracking event",
                type: "number",
                format: "float",
                example: 1.6
            ),
            new OA\Property(
                property: "timestamp",
                type: "integer",
                description: "The time of the time tracking event in epoch seconds",
                example: 1753912800
            ),
            new OA\Property(
                property: "type",
                description: "The type of the time tracking event",
                ref: "#/components/schemas/TimeTrackingEventType"
            ),
            new OA\Property(
                property: "balance",
                description: "The current balance of the current type with the time tracking event",
                type: "number",
                format: "float",
                example: 6.4
            )
        ]
    )]
    class TimeTrackingEvent implements \JsonSerializable {        
        private ?string $id;
        private readonly string $description;
        private readonly float $hours;
        private readonly int $timestamp;
        private readonly TimeTrackingEventType $type;
        private readonly float $balance;

        public function __construct(?string $id, string $description, float $hours,
            int $timestamp, TimeTrackingEventType $type, float $balance) {
            $this->id = $id;
            $this->description = $description;
            $this->hours = $hours;
            $this->timestamp = $timestamp;
            $this->type = $type;
            $this->balance = $balance;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getDescription() : string {
            return $this->description;
        }

        public function getHours() : float {
            return $this->hours;
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }
        
        public function getType() : TimeTrackingEventType {
            return $this->type;
        }

        public function getBalance() : float {
            return $this->balance;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>