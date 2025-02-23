<?php
    class Expense implements JsonSerializable {        
        private $id;
        private $description;
        private $value;
        private $currency;
        private $exchangeRate;
        private $type;
        private $mainCurrencyValue;

        public function __construct($id, $description, $value, $currency, $exchangeRate, $type, $mainCurrencyValue) {
            $this->id = $id;
            $this->description = $description;
            $this->value = $value;
            $this->currency = $currency;
            $this->exchangeRate = $exchangeRate;
            $this->type = $type;
            $this->mainCurrencyValue = $mainCurrencyValue;
        }

        public function getId() : int {
            return $this->id;
        }

        public function setId($id) : void {
            $this->id = $id;
        }

        public function getDescription() : string {
            return $this->description;
        }

        public function getValue() : float {
            return $this->value;
        }

        public function getCurrency() : string {
            return $this->currency;
        }

        public function getExchangeRate() : float {
            return $this->exchangeRate;
        }

        public function getMainCurrencyValue() : float {
            return $this->mainCurrencyValue;
        }

        public function getType() : string {
            return $this->type;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>