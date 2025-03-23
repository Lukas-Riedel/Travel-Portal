<?php
    class Photo implements JsonSerializable {        
        private $id;
        private $urlProvider;
        private $permalink;
        private $focalLength;
        private $aperture;
        private $shutterSpeed;
        private $iso;
        private $timestamp;

        public function __construct($id, $urlProvider, $permalink, $focalLength, $aperture, $shutterSpeed, $iso, $timestamp) {
            $this->id = $id;
            $this->urlProvider = $urlProvider;
            $this->permalink = $permalink;
            $this->focalLength = $focalLength;
            $this->aperture = $aperture;
            $this->shutterSpeed = $shutterSpeed;
            $this->iso = $iso;
            $this->timestamp = $timestamp;
        }

        public function getId() : int {
            return $this->id;
        }

        public function getUrl() : string {
            // Compute the URL only when it is needed to avoid unnecessary Google API calls.
            return ($this->urlProvider)();
        }

        public function getPermalink() : string {
            return $this->permalink;
        }

        public function getFocalLength() : ?float {
            return $this->focalLength;
        }

        public function getAperture() : ?float {
            return $this->aperture;
        }

        public function getShutterSpeed() : ?float {
            return $this->shutterSpeed;
        }

        public function getIso() : ?int {
            return $this->iso;
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return array_merge(array_diff_key(get_object_vars($this), ["urlProvider" => null]), ["url" => ($this->urlProvider)()]);
        }
    }
?>