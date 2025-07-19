<?php
    namespace Service\Service\Highlight;

    use Service\Service\Photo\Photo;

    class Highlight implements \JsonSerializable {        
        private readonly string $id;
        private readonly HighlightUrl $url;
        private readonly Photo $photo;
        private readonly HighlightAttributes $attributes;

        public function __construct(string $id, ?string $thumbnailUrl, ?string $fullUrl, string $photoId, ?string $photoPermalink,
            ?float $focalLength, ?float $aperture, ?float $shutterSpeed, ?int $iso, ?int $composition, ?int $sky,
            ?int $shadows, ?int $circumstances, ?int $timestamp) {
            $this->id = $id;
            $this->url = new HighlightUrl($thumbnailUrl, $fullUrl);
            $this->photo = new Photo($photoId, fn() => $fullUrl, $photoPermalink === NULL ? $fullUrl : $photoPermalink,
                $focalLength, $aperture, $shutterSpeed, $iso, $timestamp);
            $this->attributes = new HighlightAttributes($composition, $sky, $shadows, $circumstances);
        }

        public function getId() : string {
            return $this->id;
        }

        public function getUrl() : HighlightUrl {
            return $this->url;
        }

        public function getPhoto() : Photo {
            return $this->photo;
        }

        public function getAttributes() : HighlightAttributes {
            return $this->attributes;
        }

        public function getQuality() : ?float {
            return $this->attributes->getQuality();
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>