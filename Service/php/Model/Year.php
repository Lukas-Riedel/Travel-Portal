<?php
    require_once(dirname(__FILE__) . "/Highlight.php");

    class Year implements JsonSerializable {        
        private $id;
        private $mainHighlight;
        private $highlights;
        private $statistics;

        public function __construct($id, $mainHighlight, $highlights, $statistics) {
            $this->id = $id;
            $this->highlights = $highlights;
            $this->mainHighlight = $mainHighlight;
            $this->statistics = $statistics;
        }

        public function getId() : int {
            return $this->id;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        public function getHighlights() : array {
            return $this->highlights;
        }

        public function getStats() : array {
            return $this->statistics;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>