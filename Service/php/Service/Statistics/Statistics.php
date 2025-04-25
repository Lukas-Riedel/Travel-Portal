<?php
    namespace Service\Service\Statistics;

    class Statistics implements \JsonSerializable {        
        private readonly string $name;
        private readonly mixed $value;
        private readonly string $unit;

        public function __construct(string $name, mixed $value, string $unit) {
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

        public function hasValue() : bool {
            return $this->value !== NULL && (!is_array($this->value) || count($this->value) > 0);
        }

        public function withLimitedValuesCount(int $maxValuesCount) : Statistics {
            $newValue = is_array($this->value) ? array_slice($this->value, 0, $maxValuesCount) : $this->value;
            return new Statistics($this->name, $newValue, $this->unit);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }

        private function convert(mixed $value) : mixed {
            return is_numeric($value) ? floatval($value) : $value;
        }
    }
?>