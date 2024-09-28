<?php
    class TripIdentifier implements JsonSerializable {        
        private $id;
        private $name;
        private $year;
        private $mainHighlight;

        public function __construct($id, $name, $year, $mainHighlight) {
            $this->id = $id;
            $this->name = $name;
            $this->year = $year;
            $this->mainHighlight = $mainHighlight;
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

        public function getMainHighlight() {
            return $this->mainHighlight;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>