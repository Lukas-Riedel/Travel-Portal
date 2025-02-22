<?php
    class Subscription implements JsonSerializable {        
        private $id;
        private $description;
        private $value;
        private $currency;
        private $exchangeRate;
        private $expiration;

        public function __construct($id, $description, $value, $currency, $exchangeRate, $expiration) {
            $this->id = $id;
            $this->description = $description;
            $this->value = $value;
            $this->currency = $currency;
            $this->exchangeRate = $exchangeRate;
            $this->expiration = $expiration;
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

        public function getExpiration() : int {
            return $this->expiration;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this) + array(
                "mainCurrencyValue" => $this->getMainCurrencyValue());
        }
    }
?>