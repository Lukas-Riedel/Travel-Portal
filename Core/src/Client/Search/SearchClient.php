<?php
    namespace Core\Client\Search;

    interface SearchClient {
        public function createIndex(string $index, array $definition) : void;
        public function deleteIndex(string $index) : void;
        public function deleteUnusedIndexes(array $usedIndexes) : void;
        public function reassignAlias(string $alias, string $index) : void;
        public function index(string $index, array $documents) : void;
        public function search(string $index, array $query) : array;
        public function delete(string $index, string $id) : void;
    }
?>