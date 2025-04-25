<?php
    class PendingPhoto implements JsonSerializable {        
        private $id;
        private $albumId;
        private $fileName;
        private $position;
        private $replacedPhotoId;
        private $uploadToken;

        public function __construct($id, $albumId, $fileName, $position, $replacedPhotoId, $uploadToken) {
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

        public function setId($id) : void {
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