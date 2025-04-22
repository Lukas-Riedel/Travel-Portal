<?php
    require_once(dirname(__FILE__) . "/Highlight.php");

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

        public function getId() : int {
            return $this->id;
        }

        public function setId($id) : void {
            $this->id = $id;
        }

        public function getName() : string {
            return $this->name;
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

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        public function getExcerpt() : ?string {
            return $this->excerpt;
        }

        public function getLocation() : Location {
            return new Location($this->country, $this->latitude, $this->longitude, $this->timezone);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>