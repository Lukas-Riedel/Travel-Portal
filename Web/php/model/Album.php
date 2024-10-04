<?php
    class Album implements JsonSerializable {        
        private $id;
        private $name;
        private $mainPhotoId;
        private $mainImageUrl;
        private $permalink;
        private $imagesCount;
        private $indoorImagesCount;

        public function __construct($id, $name, $mainPhotoId, $mainImageUrl, $permalink, $imagesCount, $indoorImagesCount) {
            $this->id = $id;
            $this->name = $name;
            $this->mainPhotoId = $mainPhotoId;
            $this->mainImageUrl = $mainImageUrl;
            $this->permalink = $permalink;
            $this->imagesCount = $imagesCount;
            $this->indoorImagesCount = $indoorImagesCount;
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

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>