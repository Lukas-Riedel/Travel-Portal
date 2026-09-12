<?php
    namespace Core\Service\Label;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "LabelMetadata",
        type: "object",
        description: "An object representing metadata of a label",
        properties: [
            new OA\Property(
                property: "unicode",
                type: "string",
                description: "The unicode of the label",
                example: "1f3d9"
            )
        ]
    )]
    class LabelMetadata implements \JsonSerializable {       
         
        private readonly ?string $unicode;

        public function __construct(?string $unicode) {
            $this->unicode = $unicode;
        }

        public function getUnicode() : ?string {
            return $this->unicode;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
