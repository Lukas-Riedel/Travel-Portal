<?php
    namespace Service\Service\Expense;

    class Expense implements \JsonSerializable {        
        private ?string $id;
        private readonly string $description;
        private readonly float $value;
        private readonly string $currency;
        private readonly float $exchangeRate;
        private readonly ExpenseType $type;
        private readonly float $mainCurrencyValue;
        private readonly ?Subscription $subscription;

        public function __construct(?string $id, string $description, float $value, string $currency,
            float $exchangeRate, ExpenseType $type, float $mainCurrencyValue, ?Subscription $subscription) {
            $this->id = $id;
            $this->description = $description;
            $this->value = $value;
            $this->currency = $currency;
            $this->exchangeRate = $exchangeRate;
            $this->type = $type;
            $this->mainCurrencyValue = $mainCurrencyValue;
            $this->subscription = $subscription;
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
            return $this->mainCurrencyValue;
        }

        public function getType() : ExpenseType {
            return $this->type;
        }

        public function getSubscription() : ?Subscription {
            return $this->subscription;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>