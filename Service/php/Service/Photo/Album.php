<?php
    namespace Service\Service\Photo;

    class Album implements \JsonSerializable {        
        private readonly string $id;
        private readonly string $name;
        private readonly ?string $mainPhotoId;
        private readonly ?string $mainImageUrl;
        private readonly string $permalink;
        private readonly int $imagesCount;
        private readonly int $indoorImagesCount;

        public function __construct(string $id, string $name, ?string $mainPhotoId, ?string $mainImageUrl,
            string $permalink, int $imagesCount, int $indoorImagesCount) {
            $this->id = $id;
            $this->name = $name;
            $this->mainPhotoId = $mainPhotoId;
            $this->mainImageUrl = $mainImageUrl;
            $this->permalink = $permalink;
            $this->imagesCount = $imagesCount;
            $this->indoorImagesCount = $indoorImagesCount;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getMainPhotoId() : ?string {
            return $this->mainPhotoId;
        }

        public function getMainImageUrl() : ?string {
            return $this->mainImageUrl;
        }

        public function getPermalink() : string {
            return $this->permalink;
        }

        public function getImagesCount() : int {
            return $this->imagesCount;
        }

        public function getIndoorImagesCount() : int {
            return $this->indoorImagesCount;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>