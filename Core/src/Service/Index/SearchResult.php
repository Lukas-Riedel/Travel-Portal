<?php
    namespace Core\Service\Index;

    use Core\Service\Index\IndexableEntityType;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "SearchResult",
        type: "object",
        description: "A class representing a search result",
        required: ["type", "entity"],
        properties: [
            new OA\Property(
                property: "type",
                description: "The type of the searched entity",
                ref: "#/components/schemas/IndexableEntityType"
            ),
            new OA\Property(
                property: "parent",
                description: "The parent of the searched entity",
                ref: "#/components/schemas/SearchResult"
            ),
            new OA\Property(
                property: "entity",
                description: "The searched entity",
                oneOf: [
                    new OA\Schema(ref: "#/components/schemas/CategoryIdentifier"),
                    new OA\Schema(ref: "#/components/schemas/PlaceIdentifier"),
                    new OA\Schema(ref: "#/components/schemas/AirportIdentifier"),
                    new OA\Schema(ref: "#/components/schemas/AirlineIdentifier"),
                    new OA\Schema(ref: "#/components/schemas/Label"),
                    new OA\Schema(ref: "#/components/schemas/TripIdentifier"),
                    new OA\Schema(ref: "#/components/schemas/YearIdentifier"),
                    new OA\Schema(ref: "#/components/schemas/Photo"),
                    new OA\Schema(ref: "#/components/schemas/Highlight")
                ]
            )
        ]
    )]
    class SearchResult implements \JsonSerializable {
        
        private readonly IndexableEntityType $type;
        private readonly ?SearchResult $parent;
        private readonly mixed $entity;

        public function __construct(IndexableEntityType $type, ?SearchResult $parent, mixed $entity) {
            $this->type = $type;
            $this->parent = $parent;
            $this->entity = $entity;
        }

        public function getType() : IndexableEntityType {
            return $this->type;
        }

        public function getParent() : ?SearchResult {
            return $this->parent;
        }

        public function getEntity() : mixed {
            return $this->entity;
        }

        public function withReplacedParentAndEntity(?SearchResult $parent, mixed $entity) : SearchResult {
            return new SearchResult($this->type, $parent, $entity);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>