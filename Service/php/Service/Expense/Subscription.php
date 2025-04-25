<?php
    namespace Service\Service\Expense;

    class Subscription implements \JsonSerializable {        
        private ?string $id;
        private readonly string $description;
        private readonly float $value;
        private readonly string $currency;
        private readonly float $exchangeRate;
        private readonly int $expiration;

        public function __construct(?string $id, string $description, float $value, string $currency, float $exchangeRate, int $expiration) {
            $this->id = $id;
            $this->description = $description;
            $this->value = $value;
            $this->currency = $currency;
            $this->exchangeRate = $exchangeRate;
            $this->expiration = $expiration;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
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