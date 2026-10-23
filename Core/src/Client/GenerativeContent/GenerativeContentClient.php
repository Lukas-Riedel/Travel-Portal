<?php
    namespace Core\Client\GenerativeContent;

    interface GenerativeContentClient {
        public function getResponse(string $query, array $context, ?array $responseJsonSchema = null) : ?string;
        public function getChatResponse(string $prompt, string $accessToken, ?string $username, ?string $conversationId, mixed $environment) : ?GenerativeContentResult;
        public function getChatHistory(string $conversationId) : mixed;
    }
?>