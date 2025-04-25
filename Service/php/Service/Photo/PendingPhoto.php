<?php
    namespace Service\Service\Photo;

    class PendingPhoto implements \JsonSerializable {        
        private ?string $id;
        private readonly string $albumId;
        private readonly string $fileName;
        private readonly ?int $position;
        private readonly ?string $replacedPhotoId;
        private readonly string $uploadToken;

        public function __construct(?string $id, string $albumId, string $fileName, ?int $position,
            ?string $replacedPhotoId, string $uploadToken) {
            $this->id = $id;
            $this->albumId = $albumId;
            $this->fileName = $fileName;
            $this->position = $position;
            $this->replacedPhotoId = $replacedPhotoId;
            $this->uploadToken = $uploadToken;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getAlbumId() : string {
            return $this->albumId;
        }

        public function getFileName() : string {
            return $this->fileName;
        }

        public function getPosition() : ?int {
            return $this->position;
        }

        public function getReplacedPhotoId() : ?string {
            return $this->replacedPhotoId;
        }

        public function getUploadToken() : string {
            return $this->uploadToken;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>