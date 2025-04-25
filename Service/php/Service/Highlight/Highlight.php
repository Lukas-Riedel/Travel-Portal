<?php
    namespace Service\Service\Highlight;

    class Highlight implements \JsonSerializable {        
        private readonly string $id;
        private readonly HighlightUrl $url;
        private readonly ?float $focalLength;
        private readonly ?float $aperture;
        private readonly ?float $shutterSpeed;
        private readonly ?int $iso;
        private readonly ?int $timestamp;

        public function __construct(string $id, ?string $thumbnailUrl, ?string $fullUrl, ?float $focalLength,
            ?float $aperture, ?float $shutterSpeed, ?int $iso, ?int $timestamp) {
            $this->id = $id;
            $this->url = new HighlightUrl($thumbnailUrl, $fullUrl);
            $this->focalLength = $focalLength;
            $this->aperture = $aperture;
            $this->shutterSpeed = $shutterSpeed;
            $this->iso = $iso;
            $this->timestamp = $timestamp;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getUrl() : HighlightUrl {
            return $this->url;
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

        public function getTimestamp() : ?int {
            return $this->timestamp;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>