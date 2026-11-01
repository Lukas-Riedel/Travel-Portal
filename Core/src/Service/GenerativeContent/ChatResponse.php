<?php
    namespace Core\Service\GenerativeContent;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "ChatResponse",
        type: "object",
        description: "A class representing the result of a generative content chat request",
        required: ["content", "conversationId", "highlights"],
        properties: [
            new OA\Property(
                property: "content",
                description: "The generated content response text in Markdown",
                type: "string",
                example: "Here are some tips for your trip..."
            ),
            new OA\Property(
                property: "conversationId",
                description: "The identifier of the conversation",
                type: "string",
                example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
            ),
            new OA\Property(
                property: "highlights",
                description: "List of related highlights resolved from image queries",
                type: "array",
                items: new OA\Items(ref: "#/components/schemas/Highlight")
            )
        ]
    )]
    class ChatResponse implements \JsonSerializable {

        private readonly string $content;
        private readonly string $conversationId;
        private readonly array $highlights;

        public function __construct(string $content, string $conversationId, array $highlights) {
            $this->content = $content;
            $this->conversationId = $conversationId;
            $this->highlights = $highlights;
        }

        public function getContent() : string {
            return $this->content;
        }

        public function getConversationId() : string {
            return $this->conversationId;
        }

        public function getHighlights() : array {
            return $this->highlights;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
