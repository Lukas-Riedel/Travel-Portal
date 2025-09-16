<?php
    namespace Core\Client\Calendar;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "CalendarEvent",
        type: "object",
        description: "A class representing a calendar event",
        required: ["id", "summary", "start", "end", "attributes"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The identifier of the calendar event",
                type: "string",
                example: "b3ec8d68-3c83-4673-922c-962b420f0cfc"
            ),
            new OA\Property(
                property: "summary",
                description: "The summary of the calendar event",
                type: "string",
                example: "Doctor Appointment"
            ),
            new OA\Property(
                property: "location",
                description: "The location of the calendar event",
                type: "string",
                example: "Umm Suqeim 3, Dubai, United Arab Emirates"
            ),
            new OA\Property(
                property: "start",
                description: "The start time of the calendar event in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1688563200
            ),
            new OA\Property(
                property: "end",
                description: "The end time of the calendar event in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            ),
            new OA\Property(
                property: "attributes",
                description: "The attributes of the calendar event",
                type: "array",
                items: new OA\Items(type: "string")
            )
        ]
    )]
    class CalendarEvent implements \JsonSerializable {        
        private readonly string $id;
        private readonly string $summary;
        private readonly ?string $location;
        private readonly int $start;
        private readonly int $end;
        private readonly array $attributes;

        public function __construct(string $id, string $summary, ?string $location, int $start, int $end, array $attributes) {
            $this->id = $id;
            $this->summary = $summary;
            $this->location = $location;
            $this->start = $start;
            $this->end = $end;
            $this->attributes = $attributes;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getSummary() : string {
            return $this->summary;
        }

        public function getLocation() : ?string {
            return $this->location;
        }

        public function getStart() : int {
            return $this->start;
        }

        public function getEnd() : int {
            return $this->end;
        }

        public function getAttributes() : array {
            return $this->attributes;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>