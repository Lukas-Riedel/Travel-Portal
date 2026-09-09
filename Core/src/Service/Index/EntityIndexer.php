<?php
    namespace Core\Service\Index;


    interface EntityIndexer {
        public function index(DocumentBuffer $documentBuffer, IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void;
    }
?>