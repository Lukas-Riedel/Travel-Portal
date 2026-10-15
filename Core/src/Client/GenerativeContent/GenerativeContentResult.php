<?php
    namespace Core\Client\GenerativeContent;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "GenerativeContentResult",
        type: "object",
        description: "A class representing the result of a generative content chat request",
        required: ["content", "conversationId", "placeholders", "imageUrls"],
        properties: [
            new OA\Property(
                property: "content",
                description: "The generated content response text in Markdown. Named entities are represented as placeholder tokens (e.g. {{P_1}}) that the frontend expands into clickable links using the accompanying placeholders map.",
                type: "string",
                example: "Your next trip is {{P_1}}, starting on **27 December**."
            ),
            new OA\Property(
                property: "conversationId",
                description: "The identifier of the conversation",
                type: "string",
                example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
            ),
            new OA\Property(
                property: "placeholders",
                description: "Map from placeholder token key (e.g. 'P_1') to its entity descriptor.",
                type: "object",
                additionalProperties: new OA\AdditionalProperties(ref: "#/components/schemas/GenerativeContentPlaceholder")
            ),
            new OA\Property(
                property: "imageUrls",
                description: "List of related highlight thumbnail URLs resolved from image queries",
                type: "array",
                items: new OA\Items(type: "string", format: "uri")
            )
        ]
    )]
    class GenerativeContentResult implements \JsonSerializable {

        private readonly string $content;
        private readonly string $conversationId;
        private readonly array $placeholders;
        private readonly array $imageUrls;

        public function __construct(string $content, string $conversationId, array $placeholders, array $imageUrls) {
            $this->content = $content;
            $this->conversationId = $conversationId;
            $this->placeholders = $placeholders;
            $this->imageUrls = $imageUrls;
        }

        public function getContent() : string {
            return $this->content;
        }

        public function getConversationId() : string {
            return $this->conversationId;
        }

        public function getPlaceholders() : array {
            return $this->placeholders;
        }

        public function getImageUrls() : array {
            return $this->imageUrls;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
