<?php
    namespace Service\Service\Category;
    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "CompositeRegion",
        type: "object",
        description: "An object representing a composite region",
        required: ["categoryId", "includedCategoryIds", "excludedCategoryIds"],
        properties: [
            new OA\Property(
                property: "categoryId",
                type: "string",
                description: "The identifier of the category representing the composite region",
                example: "60dcf2bc-f871-4601-86ce-7775ef2931f2"
            ),
            new OA\Property(
                property: "includedCategoryIds",
                type: "array",
                description: "The list of category identifiers included in the composite region",
                items: new OA\Items(type: "string"),
                example: ["c72c9d35-82dd-48d8-be5b-63cf9a967d54", "8b1fa657-0055-492c-a571-8225bb89abfa"]
            ),
            new OA\Property(
                property: "excludedCategoryIds",
                type: "array",
                description: "The list of category identifiers excluded from the composite region",
                items: new OA\Items(type: "string"),
                example: ["45b6ad4d-9d8a-4015-b45a-3109284c4f70"]
            )
        ]
    )]
    // TODO: Replace strings with CategoryIdentifiers.
    class CompositeRegion implements \JsonSerializable {        
        private readonly string $categoryId;
        private readonly array $includedCategoryIds;
        private readonly array $excludedCategoryIds;

        public function __construct(string $categoryId, array $includedCategoryIds, array $excludedCategoryIds) {
            $this->categoryId = $categoryId;
            $this->includedCategoryIds = $includedCategoryIds;
            $this->excludedCategoryIds = $excludedCategoryIds;
        }

        public function getCategoryId() : string {
            return $this->categoryId;
        }

        public function getIncludedCategoryIds() : array {
            return $this->includedCategoryIds;
        }

        public function getExcludedCategoryIds() : array {
            return $this->excludedCategoryIds;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>