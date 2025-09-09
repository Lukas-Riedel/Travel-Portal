<?php
    namespace Core\Service\Flight;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Airport",
        type: "object",
        description: "A class representing an airport",
        required: ["shortName"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The system-generated identifier of the airport",
                type: "string",
                example: "8f3b0c9a-5cfa-4d47-bf5e-8e8f9f3a1a2b"
            ),
            new OA\Property(
                property: "shortName",
                description: "The short name of the airport",
                type: "string",
                example: "Prague"
            ),
            new OA\Property(
                property: "longName",
                description: "The long name of the airport",
                type: "string",
                example: "Václav Havel Prague Airport"
            ),
            new OA\Property(
                property: "code",
                description: "The IATA code of the airport",
                type: "string",
                example: "PRG"
            ),
            new OA\Property(
                property: "country",
                description: "The country of the airport",
                type: "string",
                example: "Czechia"
            ),
            new OA\Property(
                property: "latitude",
                description: "The latitude coordinate of the airport",
                type: "number",
                format: "float",
                example: 50.100833
            ),
            new OA\Property(
                property: "longitude",
                description: "The longitude coordinate of the airport",
                type: "number",
                format: "float",
                example: 14.26
            ),
            new OA\Property(
                property: "timezone",
                description: "The timezone of the airport",
                type: "string",
                example: "Europe/Prague"
            )
        ]
    )]
    class Airport implements \JsonSerializable {        
        private readonly ?string $id;
        private readonly string $shortName;
        private readonly ?string $longName;
        private readonly ?string $code;
        private readonly ?string $country;
        private readonly ?float $latitude;
        private readonly ?float $longitude;
        private readonly ?string $timezone;

        public function __construct(?string $id, string $shortName, ?string $longName, ?string $code,
            ?string $country, ?float $latitude, ?float $longitude, ?string $timezone) {
            $this->id = $id;
            $this->shortName = $shortName;
            $this->longName = $longName;
            $this->code = $code;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
        }

        public function getId() : ?string {
            return $this->id;
        }

        public function getShortName() : string {
            return $this->shortName;
        }

        public function getLongName() : ?string {
            return $this->longName;
        }

        public function getCode() : ?string {
            return $this->code;
        }

        public function getCountry() : ?string {
            return $this->country;
        }

        public function getLatitude() : ?float {
            return $this->latitude;
        }

        public function getLongitude() : ?float {
            return $this->longitude;
        }

        public function getTimezone() : ?string {
            return $this->timezone;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>