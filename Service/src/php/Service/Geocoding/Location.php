<?php
    namespace Service\Service\Geocoding;

    class Location implements \JsonSerializable {        
        private readonly ?string $country;
        private readonly float $latitude;
        private readonly float $longitude;
        private readonly string $timezone;

        public function __construct(?string $country, float $latitude, float $longitude, string $timezone) {
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
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

        public function getTimezone() : string {
            return $this->timezone;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>