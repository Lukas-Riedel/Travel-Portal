<?php
    class Photo implements JsonSerializable {        
        private $id;
        private $url;
        private $focalLength;
        private $aperture;
        private $shutterSpeed;
        private $iso;
        private $timestamp;

        public function __construct($id, $url, $focalLength, $aperture, $shutterSpeed, $iso, $timestamp) {
            $this->id = $id;
            $this->url = $url;
            $this->focalLength = $focalLength;
            $this->aperture = $aperture;
            $this->shutterSpeed = $shutterSpeed;
            $this->iso = $iso;
            $this->timestamp = $timestamp;
        }

        public function getId() {
            return $this->id;
        }

        public function getUrl() {
            return $this->url;
        }

        public function getFocalLength() {
            return $this->focalLength;
        }

        public function getAperture() {
            return $this->aperture;
        }

        public function getShutterSpeed() {
            return $this->shutterSpeed;
        }

        public function getIso() {
            return $this->iso;
        }

        public function getTimestamp() {
            return $this->timestamp;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>