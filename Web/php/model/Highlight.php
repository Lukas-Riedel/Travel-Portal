<?php
    require_once(dirname(__FILE__) . "/HighlightUrl.php");

    class Highlight implements JsonSerializable {        
        private $id;
        private $url;
        private $focalLength;
        private $aperture;
        private $shutterSpeed;
        private $iso;
        private $timestamp;

        public function __construct($id, $thumbnailUrl, $fullUrl, $focalLength, $aperture, $shutterSpeed, $iso, $timestamp) {
            $this->id = $id;
            $this->url = new HighlightUrl($thumbnailUrl, $fullUrl);
            $this->focalLength = $focalLength;
            $this->aperture = $aperture;
            $this->shutterSpeed = $shutterSpeed;
            $this->iso = $iso;
            $this->timestamp = $timestamp;
        }

        public function getId() : int {
            return $this->id;
        }

        public function getUrl() : HighlightUrl {
            return $this->url;
        }

        public function getFocalLength() : float {
            return $this->focalLength;
        }

        public function getAperture() : float {
            return $this->aperture;
        }

        public function getShutterSpeed() : float {
            return $this->shutterSpeed;
        }

        public function getIso() : int {
            return $this->iso;
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>