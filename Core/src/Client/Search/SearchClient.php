<?php
    namespace Core\Client\Search;

    interface SearchClient {
        public function createIndex(string $index) : void;
        public function deleteIndex(string $index) : void;
        public function index(string $index, array $documents) : void;
        public function search(string $index, string $query, array $filters, int $limit) : array;
        public function delete(string $index, string $id) : void;
    }
?>