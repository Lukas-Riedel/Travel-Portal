<?php
    namespace Core\Service\Index;

    use Core\Service\Index\IndexableEntityType;

    interface EntityIndexer {
        public function index(DocumentBuffer $documentBuffer, IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void;
    }
?>