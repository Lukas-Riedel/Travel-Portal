<?php
    class Year implements JsonSerializable {        
        private $id;
        private $mainHighlight;
        private $highlights;
        private $stats;

        public function __construct($id, $mainHighlight, $highlights, $stats) {
            $this->id = $id;
            $this->highlights = $highlights;
            $this->mainHighlight = $mainHighlight;
            $this->stats = $stats;
        }

        public function getId() {
            return $this->id;
        }

        public function getMainHighlight() {
            return $this->mainHighlight;
        }

        public function getHighlights() {
            return $this->highlights;
        }

        public function getStats() {
            return $this->stats;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>