<?php
    class ProblemValue implements JsonSerializable {
        private $name;
        private $context;

        public function __construct($name, $context) {
            $this->name = $name;
            $this->context = $context;
        }

        public function getName() {
            return $this->name;
        }

        public function getContext() {
            return $this->context;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>