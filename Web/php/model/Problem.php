<?php
    class Problem implements JsonSerializable {
        private $name;
        private $values;

        public function __construct($name, $values) {
            $this->name = $name;
            $this->values = $values;
        }

        public function getName() {
            return $this->name;
        }

        public function getValues() {
            return $this->values;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>