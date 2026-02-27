<?php
    namespace Core\Service\Photo;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "PhotoEmbedding",
        type: "object",
        description: "An object representing a photo embedding",
        required: ["id", "embeddnig"],
        properties: [
            new OA\Property(
                property: "id",
                type: "string",
                description: "The identifier of the photo",
                example: "d13e8c2e-4f9a-4c2e-b318-6fae77acda7b"
            ),
            new OA\Property(
                property: "albumId",
                type: "array",
                description: "The embedding of the photo",
                items: new OA\Items(type: "number", format: "float"),
                example: [0.0123, -0.0456, 0.789, 0.001]
            )
        ]
    )]
    class PhotoEmbedding implements \JsonSerializable { 
        private readonly string $id;
        private readonly array $embedding;

        public function __construct(string $id, array $embedding) {
            $this->id = $id;
            $this->embedding = $embedding;
        }

        public function getId() : string {
            return $this->id;
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