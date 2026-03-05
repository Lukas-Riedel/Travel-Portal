<?php
    namespace Core\Service\Highlight;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "HighlightUrl",
        type: "object",
        description: "An object representing highlight image URLs",
        required: [],
        properties: [
            new OA\Property(
                property: "thumbnail",
                description: "The URL of the thumbnail version of the highlight image",
                type: "string",
                format: "uri",
                example: "https://api.lriedel.cz/cache/highlight/thumbnail/7f6ccad1-0e4e-48ff-8cbd-d6607936eb4d.jpg"
            ),
            new OA\Property(
                property: "full",
                description: "The URL of the full-size version of the highlight image",
                type: "string",
                format: "uri",
                example: "https://api.lriedel.cz/cache/highlight/full/7f6ccad1-0e4e-48ff-8cbd-d6607936eb4d.jpg"
            )
        ]
    )]
    class HighlightUrl implements \JsonSerializable {
        
        private readonly ?string $thumbnail;
        private readonly ?string $full;

        public function __construct(?string $thumbnail, ?string $full) {
            $this->thumbnail = $thumbnail;
            $this->full = $full;
        }

        public function getThumbnail() : ?string {
            return $this->thumbnail;
        }

        public function getFull() : ?string {
            return $this->full;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>