<?php
    namespace Core\Client\Embedding;

    interface EmbeddingClient {
        public function getPhotoEmbedding(string $base64Data) : ?array;
        // TODO: Do not hardcode the source language here.
        public function getTextEmbedding(string $text, string $sourceLanguage = "cs") : ?array;
        public function getEmbeddingSimilarity(array $a, array $b) : float;
    }
?>
