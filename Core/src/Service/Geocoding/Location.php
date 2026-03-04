<?php
    namespace Core\Service\Geocoding;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Location",
        type: "object",
        description: "A class representing a geographical location",
        required: ["latitude", "longitude", "timezone"],
        properties: [
            new OA\Property(
                property: "country",
                type: "string",
                description: "The country of the location",
                example: "Czechia"
            ),
            new OA\Property(
                property: "latitude",
                type: "number",
                format: "float",
                description: "The latitude of the location",
                example: 50.0755
            ),
            new OA\Property(
                property: "longitude",
                type: "number",
                format: "float",
                description: "The longitude of the location",
                example: 14.4378
            ),
            new OA\Property(
                property: "elevation",
                type: "number",
                format: "integer",
                description: "The elevation of the location in meters",
                example: 140
            ),
            new OA\Property(
                property: "timezone",
                type: "string",
                description: "The timezone of the location",
                example: "Europe/Prague"
            )
        ]
    )]
    class Location implements \JsonSerializable {        
        private readonly ?string $country;
        private readonly float $latitude;
        private readonly float $longitude;
        private readonly int $elevation;
        private readonly string $timezone;

        public function __construct(?string $country, float $latitude, float $longitude, int $elevation, string $timezone) {
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->elevation = $elevation;
            $this->timezone = $timezone;
        }

        public function getCountry() : ?string {
            return $this->country;
        }

        public function getLatitude() : float {
            return $this->latitude;
        }

        public function getLongitude() : float {
            return $this->longitude;
        }

        public function getElevation() : int {
            return $this->elevation;
        }

        public function getTimezone() : string {
            return $this->timezone;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>