<?php
    class Expense implements JsonSerializable {        
        private $id;
        private $description;
        private $value;
        private $currency;
        private $mainCurrencyValue;
        private $type;

        public function __construct($id, $description, $value, $currency, $mainCurrencyValue, $type) {
            $this->id = $id;
            $this->description = $description;
            $this->value = $value;
            $this->currency = $currency;
            $this->mainCurrencyValue = $mainCurrencyValue;
            $this->type = $type;
        }

        public function getId() {
            return $this->id;
        }

        public function getDescription() {
            return $this->description;
        }

        public function getValue() {
            return $this->value;
        }

        public function getCurrency() {
            return $this->currency;
        }

        public function getMainCurrencyValue() {
            return $this->mainCurrencyValue;
        }

        public function getType() {
            return $this->type;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>