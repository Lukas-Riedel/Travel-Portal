<?php
    namespace Core\Service\Category;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "CategoryMetadata",
        type: "object",
        description: "An object representing metadata of a category",
        properties: [
            new OA\Property(
                property: "color",
                type: "string",
                description: "The color of the category",
                example: "#FF5733"
            ),
            new OA\Property(
                property: "unicode",
                type: "string",
                description: "The unicode of the category",
                example: "1f1ea-1f1f8"
            ),
            new OA\Property(
                property: "publicHolidaysCalendar",
                type: "string",
                description: "The URL to the public holidays calendar of the category",
                example: "https://calendar.google.com/calendar/ical/en.french%23holiday%40group.v.calendar.google.com/public/basic.ics"
            )
        ]
    )]
    class CategoryMetadata implements \JsonSerializable {       
         
        private readonly ?string $color;
        private readonly ?string $unicode;
        private readonly ?string $publicHolidaysCalendar;

        public function __construct(?string $color, ?string $unicode, ?string $publicHolidaysCalendar) {
            $this->color = $color;
            $this->unicode = $unicode;
            $this->publicHolidaysCalendar = $publicHolidaysCalendar;
        }

        public function getColor() : ?string {
            return $this->color;
        }

        public function getUnicode() : ?string {
            return $this->unicode;
        }

        public function getPublicHolidaysCalendar() : ?string {
            return $this->publicHolidaysCalendar;
        }

        public function isComplete() : bool {
            return $this->color !== null && $this->unicode !== null && $this->publicHolidaysCalendar !== null;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>