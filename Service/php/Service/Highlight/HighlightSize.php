<?php
    namespace Service\Service\Highlight;

    enum HighlightSize {
        case Full;
        case Thumbnail;

        public function getUrlColumnName() : string {
            return match($this) {
                self::Full => "full_url",
                self::Thumbnail => "thumbnail_url"
            };
        }

        public function getWidth() : int {
            return match($this) {
                self::Full => 6000,
                self::Thumbnail => 350
            };
        }

        public function getHeight() : int {
            return match($this) {
                self::Full => 4000,
                self::Thumbnail => 233
            };
        }

        public function getCachePath() : string {
            return match($this) {
                self::Full => "cache/highlight/full",
                self::Thumbnail => "cache/highlight/thumbnail"
            };
        }
    }
?>