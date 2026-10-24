<?php
    namespace Core\Client\GenerativeContent;

    interface GenerativeContentClient {
        public function getResponse(string $query, array $context, ?array $responseJsonSchema = null) : ?string;
        public function getChatResponse(array $messages, ?string $accessToken, ?string $username, mixed $environment, array $skills) : ?GenerativeContentResult;
    }
?>