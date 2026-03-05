<?php
    namespace Core\Service\Expense;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "Subscription",
        type: "object",
        description: "An object representing a subscription",
        required: ["id", "description", "value", "currency", "exchangeRate", "expiration", "mainCurrencyValue"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The unique identifier of the subscription",
                example: "c799fd70-cbc1-4624-992f-a3c740706f8a"
            ),
            new OA\Property(
                property: "description",
                type: "string",
                description: "The description of the subscription",
                example: "Deutschland Ticket"
            ),
            new OA\Property(
                property: "value",
                description: "The value of the subscription in the specified currency",
                type: "number",
                format: "float",
                example: 58
            ),
            new OA\Property(
                property: "currency",
                type: "string",
                description: "The currency of the subscription",
                example: "EUR"
            ),
            new OA\Property(
                property: "exchangeRate",
                description: "The exchange rate of the specified currency to the main currency at the time of subscription creation",
                type: "number",
                format: "float",
                example: 25.21
            ),
            new OA\Property(
                property: "expiration",
                type: "integer",
                description: "The expiration of the subscription in epoch seconds",
                example: 1753912800
            ),
            new OA\Property(
                property: "mainCurrencyValue",
                description: "The value of the subscription in the main currency",
                type: "number",
                format: "float",
                example: 1462.18
            ),
            new OA\Property(
                property: "occurrences",
                type: "integer",
                description: "The occurrences of the subscription in expenses",
                example: 4
            )
        ]
    )]
    class Subscription implements \JsonSerializable {  
              
        private ?string $id;
        private readonly string $description;
        private readonly float $value;
        private readonly string $currency;
        private readonly float $exchangeRate;
        private readonly int $expiration;
        private readonly int $occurrences;

        public function __construct(?string $id, string $description, float $value, string $currency, float $exchangeRate, int $expiration, int $occurrences) {
            $this->id = $id;
            $this->description = $description;
            $this->value = $value;
            $this->currency = $currency;
            $this->exchangeRate = $exchangeRate;
            $this->expiration = $expiration;
            $this->occurrences = $occurrences;
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

        public function getOccurrences() : int {
            return $this->occurrences;
        }

        public function isExpired() : bool {
            return $this->expiration < time();
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this) + array(
                "mainCurrencyValue" => $this->getMainCurrencyValue());
        }
    }
?>