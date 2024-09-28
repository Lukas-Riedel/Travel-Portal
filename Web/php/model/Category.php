<?php
    class Category implements JsonSerializable {        
        private $id;
        private $name;
        private $category;
        private $mainHighlight;
        private $highlights;
        private $stats;

        public function __construct($id, $name, $category, $mainHighlight, $highlights, $stats) {
            $this->id = $id;
            $this->name = $name;
            $this->category = $category;
            $this->mainHighlight = $mainHighlight;
            $this->highlights = $highlights;
            $this->stats = $stats;
        }

        public function getId() {
            return $this->id;
        }

        public function getName() {
            return $this->name;
        }

        public function getCategory() {
            return $this->category;
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