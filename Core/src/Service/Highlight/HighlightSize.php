<?php
    namespace Core\Service\Highlight;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "HighlightSize",
        type: "string",
        description: "An enum representing a highlight size"
    )]
    // TODO: Get rid of environment variables used within the enum methods -> move to the caller.
    enum HighlightSize : string {
        case Full = "full";
        case Thumbnail = "thumbnail";

        public function getUrlColumnName() : string {
            return match ($this) {
                self::Full => "full_url",
                self::Thumbnail => "thumbnail_url"
            };
        }

        public function getWidth() : int {
            return match ($this) {
                self::Full => getenv("PHOTO_FULL_WIDTH"),
                self::Thumbnail => getenv("PHOTO_THUMBNAIL_WIDTH")
            };
        }

        public function getHeight() : int {
            return match ($this) {
                self::Full => getenv("PHOTO_FULL_HEIGHT"),
                self::Thumbnail => getenv("PHOTO_THUMBNAIL_HEIGHT")
            };
        }

        public function getBucket() : string {
            return match ($this) {
                self::Full => getenv("HIGHLIGHT_PHOTO_BUCKET"),
                self::Thumbnail => getenv("HIGHLIGHT_THUMBNAIL_BUCKET")
            };
        }
    }
?>