<?php
    class Album implements JsonSerializable {        
        private $id;
        private $name;
        private $mainImageUrl;
        private $permalink;
        private $imagesCount;
        private $indoorImagesCount;
        private $isEmpty;
        private $isMainForPlace;
        private $isMainForCountry;
        private $isMainForTrip;
        private $isLowQuality;
        private $isBadWeather;

        public function __construct($id, $name, $mainImageUrl, $permalink, $imagesCount, $indoorImagesCount, $isEmpty, $isMainForPlace, $isMainForCountry, $isMainForTrip, $isLowQuality, $isBadWeather) {
            $this->id = $id;
            $this->name = $name;
            $this->mainImageUrl = $mainImageUrl;
            $this->permalink = $permalink;
            $this->imagesCount = $imagesCount;
            $this->indoorImagesCount = $indoorImagesCount;
            $this->isEmpty = $isEmpty;
            $this->isMainForPlace = $isMainForPlace;
            $this->isMainForCountry = $isMainForCountry;
            $this->isMainForTrip = $isMainForTrip;
            $this->isLowQuality = $isLowQuality;
            $this->isBadWeather = $isBadWeather;
        }

        public function getId() {
            return $this->id;
        }

        public function getName() {
            return $this->name;
        }

        public function getMainImageUrl() {
            return $this->mainImageUrl;
        }

        public function getPermalink() {
            return $this->permalink;
        }

        public function getImagesCount() {
            return $this->imagesCount;
        }

        public function getIndoorImagesCount() {
            return $this->indoorImagesCount;
        }

        public function isEmpty() {
            return $this->isEmpty;
        }

        public function isMainForPlace() {
            return $this->isMainForPlace;
        }

        public function isMainForCountry() {
            return $this->isMainForCountry;
        }

        public function isMainForTrip() {
            return $this->isMainForTrip;
        }

        public function isLowQuality() {
            return $this->isLowQuality;
        }

        public function isBadWeather() {
            return $this->isBadWeather;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>