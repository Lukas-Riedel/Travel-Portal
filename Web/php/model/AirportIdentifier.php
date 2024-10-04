<?php
    class AirportIdentifier implements JsonSerializable {        
        private $id;
        private $code;
        private $country;
        private $latitude;
        private $longitude;
        private $timezone;

        public function __construct($id, $code, $country, $latitude, $longitude, $timezone) {
            $this->id = $id;
            $this->code = $code;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
        }

        public function getId() {
            return $this->id;
        }

        public function getCode() {
            return $this->code;
        }

        public function getCountry() {
            return $this->country;
        }

        public function getLatitude() {
            return $this->latitude;
        }

        public function getLongitude() {
            return $this->longitude;
        }

        public function getTimezone() {
            return $this->timezone;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>