<?php

    class HighlightUrl implements JsonSerializable {
        private $thumbnail;
        private $full;

        public function __construct($thumbnail, $full) {
            $this->thumbnail = $thumbnail;
            $this->full = $full;
        }

        public function getThumbnail() : string {
            return $this->thumbnail;
        }

        public function getFull() : string {
            return $this->full;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>