<?php
    require_once(dirname(__FILE__) . "/Highlight.php");

    class Category implements JsonSerializable {        
        private $id;
        private $name;
        private $category;
        private $mainHighlight;
        private $highlights;
        private $statistics;

        public function __construct($id, $name, $category, $mainHighlight, $highlights, $statistics) {
            $this->id = $id;
            $this->name = $name;
            $this->category = $category;
            $this->mainHighlight = $mainHighlight;
            $this->highlights = $highlights;
            $this->statistics = $statistics;
        }

        public function getId() : int {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCategory() : string {
            return $this->category;
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