<?php
    class Label implements JsonSerializable {        
        private $id;
        private $name;

        public function __construct($id, $name) {
            $this->id = $id;
            $this->name = $name;
        }

        public function getId() : int {
            return $this->id;
        }

        public function setId($id) : void {
            $this->id = $id;
        }

        public function getName() : string {
            return $this->name;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>