<?php
    namespace Core\Service\Photo;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "PendingPhoto",
        type: "object",
        description: "An object representing a photo pending upload",
        required: ["albumId", "fileName", "batchId", "expectedBatchSize", "batchPosition", "uploadToken"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The unique identifier of the pending photo",
                example: "d13e8c2e-4f9a-4c2e-b318-6fae77acda7b"
            ),
            new OA\Property(
                property: "albumId",
                type: "string",
                description: "The identifier of the album to which the photo will be added",
                example: "fcca9204-2d39-43fc-af9a-9853d1f6cf99"
            ),
            new OA\Property(
                property: "fileName",
                type: "string",
                description: "The original name of the uploaded file",
                example: "DSC_0001.jpg"
            ),
            new OA\Property(
                property: "batchId",
                type: "string",
                description: "The identifier for the current upload batch",
                example: "39e5816f-8d47-4545-ac1b-c42bcf6a3f13"
            ),
            new OA\Property(
                property: "expectedBatchSize",
                type: "integer",
                description: "The total number of photos expected in the batch",
                example: 10
            ),
            new OA\Property(
                property: "batchPosition",
                type: "integer",
                description: "The zero-based index of the photo within the batch",
                example: 3
            ),
            new OA\Property(
                property: "replacedPhotoId",
                type: "string",
                description: "The identifier of the photo being replaced",
                example: "87122d18-1ab1-4d2a-8340-858b94dbb76e"
            ),
            new OA\Property(
                property: "uploadToken",
                type: "string",
                description: "The token retrieved by Google Photos API for the upload",
                example: "kMuvQKDPessd0F9iA92L4zYs9kXWD16RArp3IbMxTaTdW8avf6ajPwTeUvpRSibB"
            ),
        ]
    )]
    class PendingPhoto implements \JsonSerializable {        
        private ?string $id;
        private readonly string $albumId;
        private readonly string $fileName;
        private readonly string $batchId;
        private readonly int $expectedBatchSize;
        private readonly int $batchPosition;
        private readonly ?string $replacedPhotoId;
        private readonly string $uploadToken;

        public function __construct(?string $id, string $albumId, string $fileName, string $batchId,
            int $expectedBatchSize, int $batchPosition, ?string $replacedPhotoId, string $uploadToken) {
            $this->id = $id;
            $this->albumId = $albumId;
            $this->fileName = $fileName;
            $this->batchId = $batchId;
            $this->expectedBatchSize = $expectedBatchSize;
            $this->batchPosition = $batchPosition;
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

        public function getBatchId() : string {
            return $this->batchId;
        }

        public function getExpectedBatchSize() : int {
            return $this->expectedBatchSize;
        }

        public function getBatchPosition() : int {
            return $this->batchPosition;
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