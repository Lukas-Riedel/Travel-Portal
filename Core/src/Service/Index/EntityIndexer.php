<?php
    namespace Core\Service\Index;

    use Core\Service\Index\IndexableEntityType;

    interface EntityIndexer {
        public function index(IndexableEntityType $entityType) : array;
    }
?>