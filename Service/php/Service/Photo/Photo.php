<?php
    namespace Service\Service\Photo;

    class Photo implements \JsonSerializable {        
        private readonly string $id;
        private readonly mixed $urlProvider;
        private readonly ?string $permalink;
        private readonly ?float $focalLength;
        private readonly ?float $aperture;
        private readonly ?float $shutterSpeed;
        private readonly ?int $iso;
        private readonly ?int $timestamp;

        public function __construct(string $id, callable $urlProvider, ?string $permalink, ?float $focalLength,
            ?float $aperture, ?float $shutterSpeed, ?int $iso, ?int $timestamp) {
            $this->id = $id;
            $this->urlProvider = $urlProvider;
            $this->permalink = $permalink;
            $this->focalLength = $focalLength;
            $this->aperture = $aperture;
            $this->shutterSpeed = $shutterSpeed;
            $this->iso = $iso;
            $this->timestamp = $timestamp;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getUrl() : string {
            // Compute the URL only when it is needed to avoid unnecessary Google API calls.
            return ($this->urlProvider)();
        }

        public function getPermalink() : ?string {
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

        public function getTimestamp() : ?int {
            return $this->timestamp;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return array_merge(array_diff_key(get_object_vars($this), ["urlProvider" => null]), ["url" => ($this->urlProvider)()]);
        }
    }
?>