<?php
    namespace Core\Service\Place;

    use Core\Service\Category\CategoryIdentifier;    
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "CategoryPlaces",
        type: "object",
        description: "A class representing a places for the category",
        required: ["category", "places"],
        properties: [
            new OA\Property(
                property: "category",
                description: "The category for the places",
                ref: "#/components/schemas/CategoryIdentifier"
            ),
            new OA\Property(
                property: "places",
                description: "The places in the category",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Place")
            )
        ]
    )]
    class CategoryPlaces implements \JsonSerializable {
        private readonly CategoryIdentifier $category;
        private readonly array $places;

        public function __construct(CategoryIdentifier $category, array $places) {
            $this->category = $category;
            $this->places = $places;
        }

        public function getCategory() : CategoryIdentifier {
            return $this->category;
        }

        public function getPlaces() : array {
            return $this->places;
        }

        public function getPlacesCount() : int {
            return count($this->places);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>