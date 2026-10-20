<?php
    namespace Core\Client\GenerativeContent;

    interface GenerativeContentClient {
        public function getResponse(string $query, array $context, ?array $responseJsonSchema = null) : ?string;
        public function getChatResponse(string $prompt, string $accessToken, ?string $username = null, ?string $conversationId = null, mixed $environment = null) : ?GenerativeContentResult;
        public function getChatHistory(string $conversationId) : mixed;
    }
?>