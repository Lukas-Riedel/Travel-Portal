<?php
    namespace Service\Service\Category;
    
    class GeographicalRegion implements \JsonSerializable {        
        private readonly string $categoryId;
        private readonly ?string $countryCategoryId;
        private readonly int $radius;
        private readonly mixed $geoJson;

        public function __construct(string $categoryId, ?string $countryCategoryId, int $radius, mixed $geoJson) {
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