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
                property: "keywords",
                type: "array",
                description: "The keywords of the label",
                items: new OA\Items(type: "string"),
                example: ["tropical beach", "clear water", "palm trees"]
            )
        ]
    )]
    class LabelMetadata implements \JsonSerializable {       
         
        private readonly ?string $unicode;
        private readonly ?array $keywords;

        public function __construct(?string $unicode, ?array $keywords) {
            $this->unicode = $unicode;
            $this->keywords = $keywords;
        }

        public function getUnicode() : ?string {
            return $this->unicode;
        }

        public function getKeywords() : ?array {
            return $this->keywords;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
