<?php
    namespace Core\Client\GenerativeContent;

    interface GenerativeContentClient {
        public function getResponse(string $query, array $context, ?array $responseJsonSchema = null) : ?string;
        public function getChatResponse(string $prompt, string $userId, ?string $conversationId = null, ?array $responseJsonSchema = null, mixed $environment = null) : ?GenerativeContentResult;
    }
?>