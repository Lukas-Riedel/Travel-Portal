<?php
    class Subscription implements JsonSerializable {        
        private $id;
        private $description;
        private $value;
        private $currency;
        private $mainCurrencyValue;
        private $expiration;

        public function __construct($id, $description, $value, $currency, $mainCurrencyValue, $expiration) {
            $this->id = $id;
            $this->description = $description;
            $this->value = $value;
            $this->currency = $currency;
            $this->mainCurrencyValue = $mainCurrencyValue;
            $this->expiration = $expiration;
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

        public function getExpiration() {
            return $this->expiration;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>