<?php
    namespace Core\Service\Index;

    use Core\Client\Search\SearchClient;

    class DocumentBuffer {

        private readonly SearchClient $searchClient;
        private readonly IndexType $indexType;
        private readonly IndexableEntityType $entityType;
        
        private readonly string $indexName;
        private readonly int $batchSize;

        private array $documents = array();

        public function __construct(SearchClient $searchClient, IndexType $indexType,
            IndexableEntityType $entityType, string $indexName, int $batchSize) {
            $this->searchClient = $searchClient;
            $this->indexType = $indexType;
            $this->entityType = $entityType;
            $this->indexName = $indexName;
            $this->batchSize = $batchSize;
        }

        public function add(string $id, mixed $content, bool $isEmpty) : void {
            $this->documents[] = $this->getDocument($id, $content, $isEmpty);

            if (count($this->documents) >= $this->batchSize) {
                $this->flush();
            }
        }

        public function flush() : void {
            $this->searchClient->index($this->indexName, $this->documents);
            $this->documents = array();
        }

        private function getDocument(string $id, mixed $content, bool $isEmpty) : array {
            return match($this->indexType) {
                IndexType::Composite => $this->getDocumentForCompositeIndex($id, $content, $isEmpty),
                IndexType::Photo => $this->getDocumentForPhotoIndex($id, $content)
            };
        }

        private function getDocumentForCompositeIndex(string $id, array $terms, bool $isEmpty) : array {
            $name = !empty($terms) ? $terms[0] : "";

            return array(
                "id" => $this->getEntityId($id),
                "entity_type" => $this->entityType->value,
                "entity_id" => $id,
                "entity_name" => $name,
                "search_text" => implode(" ", array_unique($terms)),
                "is_empty" => $isEmpty
            );
        }

        private function getDocumentForPhotoIndex(string $id, array $data) : array {
            return array(
                "id" => $this->getEntityId($id),
                "entity_type" => $this->entityType->value,
                "photo_id" => $id,
                "embedding" => $data["embedding"],
                "place_id" => $data["placeId"],
                "trip_id" => $data["tripId"],
                "year" => $data["year"],
                "album_id" => $data["albumId"],
                "highlight_id" => $data["highlightId"],
                "iso" => $data["iso"],
                "is_place_highlight" => $data["isPlaceHighlight"],
                "is_trip_highlight" => $data["isTripHighlight"],
                "is_place_main_highlight" => $data["isPlaceMainHighlight"]
            );
        }

        private function getEntityId(string $id) : string {
            return $this->entityType->value . "_" . $id;
        }
    }
?>