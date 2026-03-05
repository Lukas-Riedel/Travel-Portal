<?php
    namespace Core\Service\Expense;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "Expense",
        type: "object",
        description: "An object representing an expense",
        required: ["id", "description", "value", "currency", "exchangeRate", "type", "mainCurrencyValue"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The unique identifier of the expense",
                example: "c799fd70-cbc1-4624-992f-a3c740706f8a"
            ),
            new OA\Property(
                property: "description",
                type: "string",
                description: "The description of the expense",
                example: "Hierapolis Archeological Site"
            ),
            new OA\Property(
                property: "value",
                description: "The value of the expense in the specified currency",
                type: "number",
                format: "float",
                example: 30
            ),
            new OA\Property(
                property: "currency",
                type: "string",
                description: "The currency of the expense",
                example: "EUR"
            ),
            new OA\Property(
                property: "exchangeRate",
                description: "The exchange rate of the specified currency to the main currency at the time of expense creation",
                type: "number",
                format: "float",
                example: 25.21
            ),
            new OA\Property(
                property: "type",
                description: "The type of the expense",
                ref: "#/components/schemas/ExpenseType"
            ),
            new OA\Property(
                property: "mainCurrencyValue",
                description: "The value of the expense in the main currency",
                type: "number",
                format: "float",
                example: 756.3
            ),
            new OA\Property(
                property: "subscription",
                description: "The subscription of the expense",
                ref: "#/components/schemas/Subscription"
            )
        ]
    )]
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