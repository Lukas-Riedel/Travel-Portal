<?php
    namespace Service\Service\Flight;

    class Airport implements \JsonSerializable {        
        private readonly ?string $id;
        private readonly string $name;
        private readonly ?string $code;
        private readonly ?string $country;
        private readonly ?float $latitude;
        private readonly ?float $longitude;
        private readonly ?string $timezone;

        public function __construct(?string $id, string $name, ?string $code,
         ?string $country, ?float $latitude, ?float $longitude, ?string $timezone) {
            $this->id = $id;
            $this->name = $name;
            $this->code = $code;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
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