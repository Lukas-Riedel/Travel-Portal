<?php
    namespace Core\Service\Index;

    use Core\Service\Index\IndexableEntityType;

    interface EntityIndexer {
        public function index(IndexType $indexType, IndexableEntityType $entityType) : array;
    }
?>