<?php
    class PlaceIdentifier implements JsonSerializable {        
        private $id;
        private $name;
        private $country;
        private $latitude;
        private $longitude;
        private $timezone;
        private $mainHighlight;
        private $excerpt;

        public function __construct($id, $name, $country, $latitude, $longitude, $timezone, $mainHighlight, $excerpt) {
            $this->id = $id;
            $this->name = $name;
            $this->country = $country;
            $this->latitude = $latitude;
            $this->longitude = $longitude;
            $this->timezone = $timezone;
            $this->mainHighlight = $mainHighlight;
            $this->excerpt = $excerpt;
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

        public function getExcerpt() {
            return $this->excerpt;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>