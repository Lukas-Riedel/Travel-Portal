<?php
    class GeographicalRegion implements JsonSerializable {        
        private $categoryId;
        private $country;
        private $radius;
        private $geoJson;

        public function __construct($categoryId, $country, $radius, $geoJson) {
            $this->categoryId = $categoryId;
            $this->country = $country;
            $this->radius = $radius;
            $this->geoJson = $geoJson;
        }

        public function getCategoryId() : string {
            return $this->categoryId;
        }

        public function getCountry() : ?string {
            return $this->country;
        }

        public function getRadius() : int {
            return $this->radius;
        }

        public function getGeoJson() : mixed {
            return $this->geoJson;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>