<?php
    namespace Service\Service\Highlight;

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