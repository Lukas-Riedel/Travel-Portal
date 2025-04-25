<?php
    namespace Service\Service\Flight;

    class AirportIdentifier implements \JsonSerializable {        
        private ?string $id;
        private readonly string $code;
        private readonly string $country;
        private readonly float $latitude;
        private readonly float $longitude;
        private readonly string $timezone;

        public function __construct(?string $id, string $code, string $country,
            float $latitude, float $longitude, string $timezone) {
            $this->id = $id;
            $this->code = $code;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getCode() : string {
            return $this->code;
        }

        public function getCountry() : string {
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