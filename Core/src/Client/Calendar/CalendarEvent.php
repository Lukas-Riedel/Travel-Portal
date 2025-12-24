<?php
    namespace Core\Client\Calendar;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "CalendarEvent",
        type: "object",
        description: "A class representing a calendar event",
        required: ["id", "summary", "start", "end", "rawStart", "rawEnd", "attributes", "allDay"],
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
                property: "rawStart",
                description: "The raw start time of the calendar event in epoch seconds",
                type: "string",
                example: "2025-12-01T07:00:00+01:00"
            ),
            new OA\Property(
                property: "rawEnd",
                description: "The raw end time of the calendar event in epoch seconds",
                type: "string",
                example: "2025-12-09T20:00:00+01:00"
            ),
            new OA\Property(
                property: "startTimezone",
                description: "The start timezone of the calendar event",
                type: "string",
                example: "Asia/Dubai"
            ),
            new OA\Property(
                property: "startTimezone",
                description: "The start timezone of the calendar event",
                type: "string",
                example: "Asia/Bangkok"
            ),
            new OA\Property(
                property: "attributes",
                description: "The attributes of the calendar event",
                type: "array",
                items: new OA\Items(type: "string")
            ),
            new OA\Property(
                property: "allDay",
                description: "Whether the calendar event is an all-day event or not",
                type: "boolean",
                example: true
            )
        ]
    )]
    class CalendarEvent implements \JsonSerializable {        
        private readonly string $id;
        private readonly string $summary;
        private readonly ?string $location;
        private readonly int $start;
        private readonly int $end;
        private readonly string $rawStart;        
        private readonly string $rawEnd;
        private readonly ?string $startTimezone;
        private readonly ?string $endTimezone;        
        private readonly array $attributes;
        private readonly bool $allDay;

        public function __construct(string $id, string $summary, ?string $location, int $start,
            int $end, string $rawStart, string $rawEnd, ?string $startTimezone, ?string $endTimezone, array $attributes, bool $allDay) {
            $this->id = $id;
            $this->summary = $summary;
            $this->location = $location;
            $this->start = $start;
            $this->end = $end;
            $this->rawStart = $rawStart;
            $this->rawEnd = $rawEnd;
            $this->startTimezone = $startTimezone;
            $this->endTimezone = $endTimezone;
            $this->attributes = $attributes;
            $this->allDay = $allDay;
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

        public function getStartTimezone() : ?string {
            return $this->startTimezone;
        }

        public function getEndTimezone() : ?string {
            return $this->endTimezone;
        }

        public function hasNormalizedStart() : bool {
            return date(DATE_RFC3339, $this->start) === $this->rawStart;
        }

        public function hasNormalizedEnd() : bool {
            return date(DATE_RFC3339, $this->end) === $this->rawEnd;
        }

        public function hasSameEffectiveStartTimezone(string $otherTimezone) : bool {
            $tzExpected = new \DateTimeZone($otherTimezone);
            $tzActual = new \DateTimeZone($this->startTimezone);
            $dtStart = new \DateTimeImmutable("@" . $this->start); 
            return $tzActual->getOffset($dtStart) === $tzExpected->getOffset($dtStart);
        }

        public function hasSameEffectiveEndTimezone(string $otherTimezone) : bool {
            $tzExpected = new \DateTimeZone($otherTimezone);
            $tzActual = new \DateTimeZone($this->endTimezone);
            $dtEnd = new \DateTimeImmutable("@" . $this->end); 
            return $tzActual->getOffset($dtEnd) === $tzExpected->getOffset($dtEnd);
        }

        public function shouldBeNormalized(string $expectedStartTimezone, string $expectedEndTimezone) : bool {
            return !$this->isAllDay() && (!$this->hasNormalizedStart() || !$this->hasNormalizedEnd()
                || !$this->hasSameEffectiveStartTimezone($expectedStartTimezone)
                || !$this->hasSameEffectiveEndTimezone($expectedEndTimezone));
        }

        public function getAttributes() : array {
            return $this->attributes;
        }

        public function isAllDay() : bool {
            return $this->allDay;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>