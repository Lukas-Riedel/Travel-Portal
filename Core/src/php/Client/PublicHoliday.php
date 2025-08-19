<?php
    namespace Core\Client;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "PublicHoliday",
        type: "object",
        description: "A class representing a public holiday",
        required: ["name", "country", "date"],
        properties: [
            new OA\Property(
                property: "name",
                description: "The name of the public holiday",
                type: "string",
                example: "Independence Day"
            ),
            new OA\Property(
                property: "country",
                description: "The country of the public holiday",
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
        private readonly string $country;
        private readonly string $date;

        public function __construct(string $name, string $country, string $date) {
            $this->name = $name;
            $this->country = $country;
            $this->date = $date;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCountry() : string {
            return $this->country;
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