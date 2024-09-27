<?php
    class Category implements JsonSerializable {        
        private $id;
        private $name;
        private $category;
        private $stats;

        public function __construct($id, $name, $category, $stats) {
            $this->id = $id;
            $this->name = $name;
            $this->category = $category;
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

        public function getStats() {
            return $this->stats;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>