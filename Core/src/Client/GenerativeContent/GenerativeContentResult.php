<?php
    namespace Core\Client\GenerativeContent;

    use OpenApi\Attributes as OA;

    #[OA\Schema(
        schema: "GenerativeContentResult",
        type: "object",
        description: "A class representing the result of a generative content chat request",
        required: ["content", "conversationId"],
        properties: [
            new OA\Property(
                property: "content",
                description: "The generated content response text",
                type: "string",
                example: "Here are some tips for your trip..."
            ),
            new OA\Property(
                property: "conversationId",
                description: "The identifier of the conversation",
                type: "string",
                example: "26135e57-fe89-4a38-82d4-5e0ad0485e28"
            )
        ]
    )]
    class GenerativeContentResult implements \JsonSerializable {

        private readonly string $content;
        private readonly string $conversationId;

        public function __construct(string $content, string $conversationId) {
            $this->content = $content;
            $this->conversationId = $conversationId;
        }

        public function getContent() : string {
            return $this->content;
        }

        public function getConversationId() : string {
            return $this->conversationId;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>
