<?php
    namespace Core\Service\Stay;

    use Core\Common\CommonConstants;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Stay",
        type: "object",
        description: "A class representing a stay",
        required: ["name", "start", "end"],
        properties: [
            new OA\Property(
                property: "name",
                description: "The name of the stay",
                type: "string",
                example: "Jumeirah Burj Al Arab"
            ),
            new OA\Property(
                property: "address",
                description: "The address of the property",
                type: "string",
                example: "Umm Suqeim 3, Dubai, United Arab Emirates"
            ),
            new OA\Property(
                property: "start",
                description: "The start time of the stay in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1688563200
            ),
            new OA\Property(
                property: "end",
                description: "The end time of the stay in epoch seconds",
                type: "integer",
                format: "int64",
                example: 1689786000
            )
        ]
    )]
    class Stay implements \JsonSerializable {
        
        private readonly string $name;
        private readonly ?string $address;
        private readonly int $start;
        private readonly int $end;

        public function __construct(string $name, ?string $address, int $start, int $end) {
            $this->name = $name;
            $this->address = $address;
            $this->start = $start;
            $this->end = $end;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getAddress() : ?string {
            return $this->address;
        }

        public function getStart() : int {
            return $this->start;
        }

        public function getEnd() : int {
            return $this->end;
        }

        public function getNightsCount() : int {
            return round(($this->end - $this->start) / CommonConstants::ONE_DAY_SECONDS) - 1;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>