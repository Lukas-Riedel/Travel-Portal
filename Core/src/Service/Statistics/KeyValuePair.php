<?php
    namespace Core\Service\Statistics;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "KeyValuePair",
        type: "object",
        description: "A class representing a key-value pair",
        required: ["key", "value"],
        properties: [
            new OA\Property(
                property: "key",
                description: "The key of the key-value pair",
                type: "string",
                example: "Prague"
            ),
            new OA\Property(
                property: "value",
                description: "The value of the key-value pair",
                oneOf: [
                    new OA\Schema(type: "string"),
                    new OA\Schema(type: "number"),
                    new OA\Schema(type: "boolean"),
                    new OA\Schema(type: "object"),
                    new OA\Schema(type: "array", items: new OA\Items())
                ],
                example: 13573
            )
        ]
    )]
    class KeyValuePair implements \JsonSerializable {
        
        private readonly string $key;
        private readonly mixed $value;

        public function __construct(string $key, mixed $value) {
            $this->key = $key;
            $this->value = $this->convert($value);
        }

        public function getKey() : string {
            return $this->key;
        }

        public function getValue() : mixed {
            return $this->value;
        }

        public function withValue(mixed $value) : KeyValuePair {
            return new KeyValuePair($this->key, $value);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
        
        private function convert(mixed $value) : mixed {
            return is_numeric($value) ? floatval($value) : $value;
        }
    }
?>