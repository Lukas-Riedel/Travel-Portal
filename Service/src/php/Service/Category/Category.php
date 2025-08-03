<?php
    namespace Service\Service\Category;

    use Service\Service\Highlight\Highlight;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "Category",
        type: "object",
        description: "A class representing a category",
        required: ["id", "name", "category", "highlights", "statistics"],
        properties: [
            new OA\Property(
                property: "id",
                description: "The identifier of the category",
                type: "string",
                example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
            ),
            new OA\Property(
                property: "name",
                description: "The name of the category",
                type: "string",
                example: "Europe"
            ),
            new OA\Property(
                property: "category",
                description: "The category of the category",
                ref: "#/components/schemas/CategoryCategory"
            ),
            new OA\Property(
                property: "metadata",
                description: "The metadata of the category",
                ref: "#/components/schemas/CategoryMetadata"
            ),
            new OA\Property(
                property: "mainHighlight",
                description: "The main highlight of the category",
                ref: "#/components/schemas/Highlight"
            ),
            new OA\Property(
                property: "highlights",
                description: "The highlights of the category",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Highlight")
            ),
            new OA\Property(
                property: "statistics",
                description: "The statistics of the category",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/KeyValuePair")
            )
        ]
    )]
    class Category implements \JsonSerializable {        
        private readonly string $id;
        private readonly string $name;
        private readonly CategoryCategory $category;
        private readonly ?CategoryMetadata $metadata;
        private readonly ?Highlight $mainHighlight;
        private readonly array $highlights;
        private readonly array $statistics;

        public function __construct(string $id, string $name, CategoryCategory $category, ?CategoryMetadata $metadata,
            ?Highlight $mainHighlight, array $highlights, array $statistics) {
            $this->id = $id;
            $this->name = $name;
            $this->category = $category;
            $this->metadata = $metadata;
            $this->mainHighlight = $mainHighlight;
            $this->highlights = $highlights;
            $this->statistics = $statistics;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCategory() : CategoryCategory {
            return $this->category;
        }

        public function getMetadata() : ?CategoryMetadata {
            return $this->metadata;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        public function getHighlights() : array {
            return $this->highlights;
        }

        public function getStats() : array {
            return $this->statistics;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>