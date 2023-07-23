<?php
    class TripIdentifier implements JsonSerializable {        
        private $id;
        private $name;
        private $year;

        public function __construct($id, $name, $year) {
            $this->id = $id;
            $this->name = $name;
            $this->year = $year;
        }

        public function getId() {
            return $this->id;
        }

        public function getName() {
            return $this->name;
        }

        public function getYear() {
            return $this->year;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>