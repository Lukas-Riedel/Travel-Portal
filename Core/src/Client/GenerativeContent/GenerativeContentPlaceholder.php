<?php
    namespace Core\Client\GenerativeContent;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "GenerativeContentPlaceholder",
        type: "object",
        description: "An entity descriptor used to expand a placeholder token in a generative content message into a clickable in-app link",
        required: ["entityType", "entityId", "label"],
        properties: [
            // TODO: Eventually define an enum similar to IndexableEntityType.
            new OA\Property(
                property: "entityType",
                description: "The entity type as used in the Travel Portal URL path (e.g. 'trip', 'place', 'category', 'airport', 'airline', 'label', 'year')",
                type: "string",
                example: "trip"
            ),
            new OA\Property(
                property: "entityId",
                description: "The UUID or identifier of the entity",
                type: "string",
                example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
            ),
            new OA\Property(
                property: "label",
                description: "The human-readable display name used as the link text, in the language of the user's message",
                type: "string",
                example: "Vietnam 2027"
            )
        ]
    )]
    class GenerativeContentPlaceholder implements \JsonSerializable {

        private readonly string $entityType;
        private readonly string $entityId;
        private readonly string $label;

        public function __construct(string $entityType, string $entityId, string $label) {
            $this->entityType = $entityType;
            $this->entityId = $entityId;
            $this->label = $label;
        }

        public function getEntityType() : string {
            return $this->entityType;
        }

        public function getEntityId() : string {
            return $this->entityId;
        }

        public function getLabel() : string {
            return $this->label;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
