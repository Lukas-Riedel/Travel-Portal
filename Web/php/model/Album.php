<?php
    class Album implements JsonSerializable {        
        private $id;
        private $name;
        private $mainPhotoId;
        private $mainImageUrl;
        private $permalink;
        private $imagesCount;
        private $indoorImagesCount;
        private $isMainForPlace;
        private $isMainForCountry;
        private $isMainForTrip;
        private $isLowQuality;
        private $isBadWeather;

        public function __construct($id, $name, $mainPhotoId, $mainImageUrl, $permalink, $imagesCount, $indoorImagesCount, $isMainForPlace, $isMainForCountry, $isMainForTrip, $isLowQuality, $isBadWeather) {
            $this->id = $id;
            $this->name = $name;
            $this->mainPhotoId = $mainPhotoId;
            $this->mainImageUrl = $mainImageUrl;
            $this->permalink = $permalink;
            $this->imagesCount = $imagesCount;
            $this->indoorImagesCount = $indoorImagesCount;
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

        public function getMainPhotoId() {
            return $this->mainPhotoId;
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