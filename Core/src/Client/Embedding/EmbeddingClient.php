<?php
    namespace Core\Client\Embedding;

    interface EmbeddingClient {
        public function getPhotoEmbedding(string $base64Data) : ?array;
        public function getTextEmbedding(string $text, string $language) : ?array;
        public function getEmbeddingSimilarity(array $a, array $b) : float;
    }
?>
