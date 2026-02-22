<?php
    namespace Core\Service\Expense;

    use OpenApi\Attributes as OA;
    
    #[OA\Schema(
        schema: "Voucher",
        type: "object",
        description: "An object representing a voucher",
        required: ["id", "code", "issuer", "value", "currency"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The unique identifier of the voucher",
                example: "c799fd70-cbc1-4624-992f-a3c740706f8a"
            ),
            new OA\Property(
                property: "code",
                type: "string",
                description: "The code of the voucher",
                example: "REB39M43HAMA"
            ),
            new OA\Property(
                property: "issuer",
                type: "string",
                description: "The issuer of the voucher",
                example: "FLIXBUS"
            ),
            new OA\Property(
                property: "value",
                description: "The value of the voucher in the specified currency",
                type: "number",
                format: "float",
                example: 58
            ),
            new OA\Property(
                property: "currency",
                type: "string",
                description: "The currency of the voucher",
                example: "EUR"
            ),
            new OA\Property(
                property: "expiration",
                type: "integer",
                description: "The expiration of the voucher in epoch seconds",
                example: 1753912800
            )
        ]
    )]
    class Voucher implements \JsonSerializable {        
        private ?string $id;
        private readonly string $code;
        private readonly string $issuer;
        private readonly float $value;
        private readonly string $currency;
        private readonly ?int $expiration;

        public function __construct(?string $id, string $code, string $issuer, float $value, string $currency, ?int $expiration) {
            $this->id = $id;
            $this->code = $code;
            $this->issuer = $issuer;
            $this->value = $value;
            $this->currency = $currency;
            $this->expiration = $expiration;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getCode() : string {
            return $this->code;
        }

        public function getIssuer() : string {
            return $this->issuer;
        }

        public function getValue() : float {
            return $this->value;
        }

        public function getCurrency() : string {
            return $this->currency;
        }

        public function getExpiration() : ?int {
            return $this->expiration;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>