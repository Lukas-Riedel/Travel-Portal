<?php
    namespace Service\Service\Photo;

    class Album implements \JsonSerializable {      
        private const ALBUM_NAME_PATTERN = "/^(.*) (\d{1,2}\.\d{1,2}\.\d{4})$/";

        private readonly string $id;
        private readonly string $name;
        private readonly ?Photo $mainPhoto;
        private readonly ?string $mainImageUrl;
        private readonly string $permalink;
        private readonly int $imagesCount;
        private readonly int $indoorImagesCount;
        private readonly ?int $uploadingStart;
        private readonly ?float $uploadingProgress;

        public function __construct(string $id, string $name, ?Photo $mainPhoto, ?string $mainImageUrl,
            string $permalink, int $imagesCount, int $indoorImagesCount, ?int $uploadingStart, ?float $uploadingProgress) {
            $this->id = $id;
            $this->name = $name;
            $this->mainPhoto = $mainPhoto;
            $this->mainImageUrl = $mainImageUrl;
            $this->permalink = $permalink;
            $this->imagesCount = $imagesCount;
            $this->indoorImagesCount = $indoorImagesCount;
            $this->uploadingStart = $uploadingStart;
            $this->uploadingProgress = $uploadingProgress;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getMainPhoto() : ?Photo {
            return $this->mainPhoto;
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
        
        public function getUploadingStart() : ?int {
            return $this->uploadingStart;
        }

        public function getUploadingProgress() : ?float {
            return $this->uploadingProgress;
        }

        public function getPlaceName() : string {
            return $this->parseAlbumName()[1];
        }

        public function getPlaceDateString() : string {
            return $this->parseAlbumName()[2];
        }

        private function parseAlbumName() : array {
            $matches = array();
            if (preg_match(self::ALBUM_NAME_PATTERN, $this->name, $matches)) {
                return $matches;
            }

            throw new \InvalidArgumentException("The album name '" . $this->name . "' is invalid.");
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>