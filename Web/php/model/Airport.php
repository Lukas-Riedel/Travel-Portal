<?php
    class Airport implements JsonSerializable {        
        private $id;
        private $name;
        private $code;
        private $country;
        private $latitude;
        private $longitude;
        private $timezone;

        public function __construct($id, $name, $code, $country, $latitude, $longitude, $timezone) {
            $this->id = $id;
            $this->name = $name;
            $this->code = $code;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
        }

        public function getId() {
            return $this->id;
        }

        public function getName() {
            return $this->name;
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
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>