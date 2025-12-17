<?php
    namespace Core\Service\Highlight;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "HighlightSize",
        type: "string",
        description: "An enum representing a highlight size"
    )]
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
                self::Full => 2400,
                self::Thumbnail => 350
            };
        }

        public function getHeight() : int {
            return match ($this) {
                self::Full => 1600,
                self::Thumbnail => 233
            };
        }

        public function getBucket() : string {
            return match ($this) {
                self::Full => "highlight-photos",
                self::Thumbnail => "highlight-thumbnails"
            };
        }
    }
?>