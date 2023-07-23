<?php
    class Statistics implements JsonSerializable {        
        private $name;
        private $value;
        private $unit;

        public function __construct($name, $value, $unit) {
            $this->name = $name;
            $this->value = $value;
            $this->unit = $unit;
        }

        public function getName() {
            return $this->name;
        }

        public function getValue() {
            return $this->value;
        }

        public function getUnit() {
            return $this->unit;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>