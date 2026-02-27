<?php
    namespace Core\Service\Index;

    use Core\Service\Index\IndexableEntityType;
    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "SearchResult",
        type: "object",
        description: "A class representing a a search result",
        required: ["type", "entity"],
        properties: [
            new OA\Property(
                property: "type",
                description: "The type of the searched entity",
                ref: "#/components/schemas/IndexableEntityType"
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
                    new OA\Schema(ref: "#/components/schemas/YearIdentifier")  
                ]
            )
        ]
    )]
    class SearchResult implements \JsonSerializable {
        private readonly IndexableEntityType $type;
        private readonly mixed $entity;

        public function __construct(IndexableEntityType $type, mixed $entity) {
            $this->type = $type;
            $this->entity = $entity;
        }

        public function getType() : IndexableEntityType {
            return $this->type;
        }

        public function getEntity() : mixed {
            return $this->entity;
        }

        public function withReplacedEntity(mixed $entity) : SearchResult {
            return new SearchResult($this->type, $entity);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>