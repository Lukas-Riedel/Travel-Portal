<?php
    namespace Core\Service\Photo;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "PhotoEmbedding",
        type: "object",
        description: "An object representing a photo embedding",
        required: ["id", "embedding"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The identifier of the photo",
                example: "d13e8c2e-4f9a-4c2e-b318-6fae77acda7b"
            ),
            new OA\Property(
                property: "iso",
                type: "integer",
                description: "The ISO settings of the photo",
                example: 100
            ),
            new OA\Property(
                property: "embedding",
                type: "array",
                description: "The embedding of the photo",
                items: new OA\Items(type: "number", format: "float"),
                example: [0.0123, -0.0456, 0.789, 0.001]
            )
        ]
    )]
    class PhotoEmbedding implements \JsonSerializable { 
        
        private readonly string $id;
        private readonly ?int $iso;
        private readonly array $embedding;

        public function __construct(string $id, ?int $iso, array $embedding) {
            $this->id = $id;
            $this->iso = $iso;
            $this->embedding = $embedding;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getIso() : ?int {
            return $this->iso;
        }

        public function getEmbedding() : array {
            return $this->embedding;
        }
        
        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>