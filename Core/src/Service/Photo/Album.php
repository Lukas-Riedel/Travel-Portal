<?php
    namespace Core\Service\Photo;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Album",
        type: "object",
        description: "An object representing a photo album",
        required: ["id", "name", "permalink", "imagesCount", "indoorImagesCount"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The unique identifier of the album",
                example: "a8350d9e-4da7-4ff8-8ecc-f813bc33a701"
            ),
            new OA\Property(
                property: "name",
                type: "string",
                description: "The name of the album",
                example: "Prague 25.7.2024"
            ),
            new OA\Property(
                property: "mainPhoto",
                ref: "#/components/schemas/Photo",
                description: "The main photo of the album"
            ),
            new OA\Property(
                property: "mainImageUrl",
                type: "string",
                format: "uri",
                description: "The URL of the main image of the album",
                example: "https://example.com/images/album-main.jpg"
            ),
            new OA\Property(
                property: "permalink",
                type: "string",
                format: "uri",
                description: "The permalink URL of the album",
                example: "https://photos.example.com/album/album-1234abcd"
            ),
            new OA\Property(
                property: "imagesCount",
                type: "integer",
                description: "The total number of images in the album",
                example: 42
            ),
            new OA\Property(
                property: "indoorImagesCount",
                type: "integer",
                description: "The number of indoor images in the album",
                example: 15
            ),
            new OA\Property(
                property: "reviewed",
                type: "boolean",
                description: "Whether the album has been reviewed or not",
                example: true
            ),            
            new OA\Property(
                property: "uploadingStart",
                type: "integer",
                description: "The epoch timestamp when the uploading started",
                example: 1689859200
            ),
            new OA\Property(
                property: "uploadingProgress",
                type: "number",
                format: "float",
                description: "The uploading progress in percents",
                example: 77
            )
        ]
    )]
    class Album implements \JsonSerializable {      
        
        private const ALBUM_NAME_PATTERN = "/^(.*) (\d{1,2}\.\d{1,2}\.\d{4})$/";

        private readonly string $id;
        private readonly string $name;
        private readonly ?Photo $mainPhoto;
        private readonly ?string $mainImageUrl;
        private readonly string $permalink;
        private readonly int $imagesCount;
        private readonly int $indoorImagesCount;
        private readonly bool $reviewed;
        private readonly ?int $uploadingStart;
        private readonly ?float $uploadingProgress;

        public function __construct(string $id, string $name, ?Photo $mainPhoto, ?string $mainImageUrl,
            string $permalink, int $imagesCount, int $indoorImagesCount, bool $reviewed, ?int $uploadingStart, ?float $uploadingProgress) {
            $this->id = $id;
            $this->name = $name;
            $this->mainPhoto = $mainPhoto;
            $this->mainImageUrl = $mainImageUrl;
            $this->permalink = $permalink;
            $this->imagesCount = $imagesCount;
            $this->indoorImagesCount = $indoorImagesCount;
            $this->reviewed = $reviewed;
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

        public function isReviewed() : bool {
            return $this->reviewed;
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