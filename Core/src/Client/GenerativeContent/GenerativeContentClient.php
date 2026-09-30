<?php
    namespace Core\Client\GenerativeContent;

    interface GenerativeContentClient {
        public function getResponse(string $query, array $context, ?array $responseJsonSchema = null) : ?string;
    }
?>