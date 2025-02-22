<?php
    class Expense implements JsonSerializable {        
        private $id;
        private $description;
        private $value;
        private $currency;
        private $exchangeRate;
        private $type;

        public function __construct($id, $description, $value, $currency, $exchangeRate, $type) {
            $this->id = $id;
            $this->description = $description;
            $this->value = $value;
            $this->currency = $currency;
            $this->exchangeRate = $exchangeRate;
            $this->type = $type;
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
            return $this->value * $this->exchangeRate;
        }

        public function getType() : string {
            return $this->type;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this) + array(
                "mainCurrencyValue" => $this->getMainCurrencyValue());
        }
    }
?>