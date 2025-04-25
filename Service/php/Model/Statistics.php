<?php
    class Statistics implements JsonSerializable {        
        private $name;
        private $value;
        private $unit;

        public function __construct($name, $value, $unit) {
            $this->name = $name;
            $this->value = $this->convert($value);
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

        public function hasValue() : bool {
            return $this->value !== NULL && (!is_array($this->value) || count($this->value) > 0);
        }

        public function withLimitedValuesCount(int $maxValuesCount) : Statistics {
            $newValue = is_array($this->value) ? array_slice($this->value, 0, $maxValuesCount) : $this->value;
            return new Statistics($this->name, $newValue, $this->unit);
        }

        private function convert($value) {
            return is_numeric($value) ? floatval($value) : $value;
        }
    }
?>