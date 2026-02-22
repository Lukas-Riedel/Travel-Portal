<?php
    namespace Core\Client\Calendar;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "PublicHoliday",
        type: "object",
        description: "A class representing a public holiday",
        required: ["name", "category", "date"],
        properties: [
            new OA\Property(
                property: "name",
                description: "The name of the public holiday",
                type: "string",
                example: "Independence Day"
            ),
            new OA\Property(
                property: "category",
                description: "The category of the public holiday",
                type: "string",
                example: "United States"
            ),
            new OA\Property(
                property: "date",
                description: "The date of the public holiday",
                type: "string",
                example: "4.7.2025"
            )
        ]
    )]
    class PublicHoliday implements \JsonSerializable {
        private readonly string $name;
        private readonly string $category;
        private readonly string $date;

        public function __construct(string $name, string $category, string $date) {
            $this->name = $name;
            $this->category = $category;
            $this->date = $date;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCategory() : string {
            return $this->category;
        }

        public function getDate() : string {
            return $this->date;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>