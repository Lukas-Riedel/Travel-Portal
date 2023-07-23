<?php
    class CategoryIdentifier implements JsonSerializable {        
        private $id;
        private $name;
        private $category;

        public function __construct($id, $name, $category) {
            $this->id = $id;
            $this->name = $name;
            $this->category = $category;
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

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>