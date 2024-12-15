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

        public function getName() : string {
            return $this->name;
        }

        public function getValue() : mixed {
            return $this->value;
        }

        public function getUnit() : string {
            return $this->unit;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>