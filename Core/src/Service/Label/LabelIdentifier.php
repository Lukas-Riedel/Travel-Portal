<?php
    namespace Core\Service\Label;

    use Core\Service\Highlight\Highlight;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "LabelIdentifier",
        type: "object",
        description: "A class representing a label identifier",
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
            ),
            new OA\Property(
                property: "metadata",
                description: "The metadata of the label",
                ref: "#/components/schemas/LabelMetadata"
            ),
            new OA\Property(
                property: "mainHighlight",
                description: "The main highlight of the label",
                ref: "#/components/schemas/Highlight"
            )
        ]
    )]
    class LabelIdentifier implements \JsonSerializable {

        private ?string $id;
        private readonly string $name;
        private readonly ?LabelMetadata $metadata;
        private readonly ?Highlight $mainHighlight;

        public function __construct(?string $id, string $name, ?LabelMetadata $metadata, ?Highlight $mainHighlight) {
            $this->id = $id;
            $this->name = $name;
            $this->metadata = $metadata;
            $this->mainHighlight = $mainHighlight;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getMetadata() : ?LabelMetadata {
            return $this->metadata;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>