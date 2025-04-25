<?php
    class GeographicalRegion implements JsonSerializable {        
        private $categoryId;
        private $countryCategoryId;
        private $radius;
        private $geoJson;

        public function __construct($categoryId, $countryCategoryId, $radius, $geoJson) {
            $this->categoryId = $categoryId;
            $this->countryCategoryId = $countryCategoryId;
            $this->radius = $radius;
            $this->geoJson = $geoJson;
        }

        public function getCategoryId() : string {
            return $this->categoryId;
        }

        public function getCountryCategoryId() : ?string {
            return $this->countryCategoryId;
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