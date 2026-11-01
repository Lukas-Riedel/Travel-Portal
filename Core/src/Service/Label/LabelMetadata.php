<?php
    namespace Core\Service\Label;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "LabelMetadata",
        type: "object",
        description: "A class representing metadata of a label",
        properties: [
            new OA\Property(
                property: "unicode",
                type: "string",
                description: "The unicode of the label",
                example: "1f3d9"
            ),
            new OA\Property(
                property: "description",
                type: "string",
                description: "The description of the label",
                example: "A tropical beach with clear water and palm trees"
            )
        ]
    )]
    class LabelMetadata implements \JsonSerializable {       
         
        private readonly ?string $unicode;
        private readonly ?string $description;

        public function __construct(?string $unicode, ?string $description) {
            $this->unicode = $unicode;
            $this->description = $description;
        }

        public function getUnicode() : ?string {
            return $this->unicode;
        }

        public function getDescription() : ?string {
            return $this->description;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
