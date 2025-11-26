<?php
    namespace Core\Service\Statistics;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Statistics",
        type: "object",
        description: "A class representing a statistics record",
        required: ["name", "value"],
        properties: [
            new OA\Property(
                property: "name",
                description: "The name of the statistics record",
                type: "string",
                example: "TOTAL_PHOTOS_COUNT"
            ),
            new OA\Property(
                property: "value",
                description: "The value of statistics record",
                oneOf: [
                    new OA\Schema(type: "string"),
                    new OA\Schema(type: "number"),
                    new OA\Schema(type: "boolean"),
                    new OA\Schema(type: "array", items: new OA\Items(ref: "#/components/schemas/KeyValuePair"))
                ],
                example: 13573
            ),
            new OA\Property(
                property: "unit",
                description: "The unit of the statistics record",
                ref: "#/components/schemas/StatisticsUnit"
            )
        ]
    )]
    class Statistics implements \JsonSerializable {        
        private readonly string $name;
        private readonly mixed $value;
        private readonly StatisticsUnit $unit;

        public function __construct(string $name, mixed $value, StatisticsUnit $unit) {
            $this->name = $name;
            $this->value = $this->convert($value);
            $this->unit = $unit;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getValue() : mixed {
            return $this->value;
        }

        public function getUnit() : StatisticsUnit {
            return $this->unit;
        }

        public function hasValue() : bool {
            return $this->value !== null && (!is_array($this->value) || count($this->value) > 0);
        }

        public function withLimitedValuesCount(int $maxValuesCount) : Statistics {
            $newValue = is_array($this->value) ? array_slice($this->value, 0, $maxValuesCount) : $this->value;
            return new Statistics($this->name, $newValue, $this->unit);
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