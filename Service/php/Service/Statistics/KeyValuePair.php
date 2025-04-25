<?php
    namespace Service\Service\Statistics;

    class KeyValuePair implements \JsonSerializable {
        private readonly string $key;
        private readonly mixed $value;

        public function __construct(string $key, mixed $value) {
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
        
        private function convert(mixed $value) : mixed {
            return is_numeric($value) ? floatval($value) : $value;
        }
    }
?>