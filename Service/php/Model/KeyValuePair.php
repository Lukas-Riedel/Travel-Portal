<?php
    class KeyValuePair implements JsonSerializable {
        private $key;
        private $value;

        public function __construct($key, $value) {
            $this->key = $key;
            $this->value = $this->convert($value);
        }

        public function getKey() : string {
            return $this->key;
        }

        public function getValue() : mixed {
            return $this->value;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
        
        private function convert($value) {
            return is_numeric($value) ? floatval($value) : $value;
        }
    }
?>