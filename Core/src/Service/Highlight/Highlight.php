<?php
    namespace Core\Service\Highlight;

    use Core\Service\Photo\Photo;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Highlight",
        type: "object",
        description: "An object representing a highlight",
        required: ["id", "url", "photo", "attributes"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The unique identifier of the highlight",
                example: "c799fd70-cbc1-4624-992f-a3c740706f8a"
            ),
            new OA\Property(
                property: "url",
                description: "The URLs of the highlight",
                ref: "#/components/schemas/HighlightUrl"
            ),
            new OA\Property(
                property: "photo",
                description: "The highlighted photo",
                ref: "#/components/schemas/Photo"
            ),
            new OA\Property(
                property: "attributes",
                description: "The quality attributes of the highlight",
                ref: "#/components/schemas/HighlightAttributes"
            )
        ]
    )]
    class Highlight implements \JsonSerializable {        
        private readonly string $id;
        private readonly HighlightUrl $url;
        private readonly Photo $photo;
        private readonly HighlightAttributes $attributes;

        public function __construct(string $id, ?string $thumbnailUrl, ?string $fullUrl, string $photoId, ?string $photoPermalink,
            ?string $camera, ?float $focalLength, ?float $aperture, ?float $shutterSpeed, ?int $iso, ?int $composition, ?int $sky,
            ?int $shadows, ?int $circumstances, ?int $atmosphere, ?int $timestamp, ?float $sunAltitude, ?float $sunAzimuth) {
            $this->id = $id;
            $this->url = new HighlightUrl($thumbnailUrl, $fullUrl);
            $this->photo = new Photo($photoId, fn() => $fullUrl, $photoPermalink === null ? $fullUrl : $photoPermalink, $camera,
                $focalLength, $aperture, $shutterSpeed, $iso, $timestamp, $sunAltitude, $sunAzimuth);
            $this->attributes = new HighlightAttributes($composition, $sky, $shadows, $circumstances, $atmosphere);
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