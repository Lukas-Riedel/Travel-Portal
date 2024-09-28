<?php
    class PlaceIdentifier implements JsonSerializable {        
        private $id;
        private $name;
        private $country;
        private $latitude;
        private $longitude;
        private $timezone;
        private $mainHighlight;

        public function __construct($id, $name, $country, $latitude, $longitude, $timezone, $mainHighlight) {
            $this->id = $id;
            $this->name = $name;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
            $this->mainHighlight = $mainHighlight;
        }

        public function getId() {
            return $this->id;
        }

        public function getName() {
            return $this->name;
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

        public function getMainHighlight() {
            return $this->mainHighlight;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>