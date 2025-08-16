<?php
    namespace Core\Service\Label;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Label",
        type: "object",
        description: "An object representing a label",
        required: ["id", "name"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The unique identifier of the label",
                example: "c799fd70-cbc1-4624-992f-a3c740706f8a"
            ),
            new OA\Property(
                property: "name",
                type: "string",
                description: "The name of the label",
                example: "City"
            )
        ]
    )]
    class Label implements \JsonSerializable {     

        private readonly string $id;
        private readonly string $name;

        public function __construct(string $id, string $name) {
            $this->id = $id;
            $this->name = $name;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>