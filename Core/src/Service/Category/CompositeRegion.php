<?php
    namespace Core\Service\Category;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "CompositeRegion",
        type: "object",
        description: "An object representing a composite region",
        required: ["category", "includedCategories", "excludedCategories"],
        properties: [
            new OA\Property(
                property: "category",
                description: "The category representing the composite region",
                ref: "#/components/schemas/CategoryIdentifier"
            ),
            new OA\Property(
                property: "includedCategories",
                type: "array",
                description: "The list of category identifiers included in the composite region",
                items: new OA\Items(ref: "#/components/schemas/CategoryIdentifier")
            ),
            new OA\Property(
                property: "excludedCategories",
                type: "array",
                description: "The list of category identifiers excluded from the composite region",
                items: new OA\Items(ref: "#/components/schemas/CategoryIdentifier")
            )
        ]
    )]
    class CompositeRegion implements \JsonSerializable {        
        private readonly CategoryIdentifier $category;
        private readonly array $includedCategories;
        private readonly array $excludedCategories;

        public function __construct(CategoryIdentifier $category, array $includedCategories, array $excludedCategories) {
            $this->category = $category;
            $this->includedCategories = $includedCategories;
            $this->excludedCategories = $excludedCategories;
        }

        public function getCategory() : CategoryIdentifier {
            return $this->category;
        }

        public function getIncludedCategories() : array {
            return $this->includedCategories;
        }

        public function getExcludedCategories() : array {
            return $this->excludedCategories;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>