<?php
    namespace Core\Service\Index;

    use Core\Client\Search\SearchClient;
    use Ramsey\Uuid\Uuid;

    class IndexService {

        private const BATCH_SIZE = 500;

        private readonly IndexQueryDefinitionFactory $indexQueryDefinitionFactory;
    
        private readonly SearchClient $searchClient;

        private readonly string $compositeIndexName;
        private readonly string $photoIndexName;

        private array $entityIndexers = array();

        public function __construct(SearchClient $searchClient, string $compositeIndexName, string $photoIndexName) {
            $this->indexQueryDefinitionFactory = new IndexQueryDefinitionFactory();
            $this->searchClient = $searchClient;
            $this->compositeIndexName = $compositeIndexName;
            $this->photoIndexName = $photoIndexName;
        }

        public function setEntityIndexers(array $entityIndexers) : void {
            $this->entityIndexers = $entityIndexers;
        }

        public function search(string $query, int $limit, array $allowedEntityTypes) : array {
            return array_map(fn($searchResult) => new SearchResult(IndexableEntityType::from($searchResult["entity_type"]), $searchResult["entity_id"]),
                $this->searchClient->search($this->compositeIndexName, $this->indexQueryDefinitionFactory->createCompositeIndexSearchQuery($query, $limit, $allowedEntityTypes)));
        }

        public function getNearestNeighbourPhotoIds(array $embedding, int $limit, bool $mainHighlightsOnly = true) : array {    
            $response = $this->searchClient->search($this->photoIndexName,
                $this->indexQueryDefinitionFactory->createPhotoNearestNeighbourQuery($embedding, $limit, $mainHighlightsOnly));

            if (!isset($response["hits"]["hits"])) {
                return array();
            }

            return array_map(fn($hit) => new NearestNeighbour($hit["_source"]["photo_id"], (float)$hit["_score"]), $response["hits"]["hits"]);
        }

        public function reindex() : void {            
            foreach (IndexType::cases() as &$indexType) {
                $temporaryIndexName = Uuid::uuid4()->toString();
                $this->searchClient->createIndex($temporaryIndexName, $this->getIndexDefinition($indexType));

                foreach (IndexableEntityType::cases() as &$entityType) {
                    $this->doIndex($temporaryIndexName, $indexType, $entityType, null);
                }

                $this->searchClient->reassignAlias($this->getIndexName($indexType), $temporaryIndexName);
            }

            $this->searchClient->deleteUnusedIndexes(array_map(fn($indexType) => $this->getIndexName($indexType), IndexType::cases()));
        }

        public function index(IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void {
            $this->doIndex($this->getIndexName($indexType), $indexType, $entityType, $entityId);
        }

        private function doIndex(string $indexName, IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void {
            foreach ($this->entityIndexers as &$entityIndexer) {
                $documents = $entityIndexer->index($indexType, $entityType, $entityId);
                if (empty($documents)) {
                    continue;
                }

                $mappedDocuments = array();
                foreach ($documents as $id => $terms) {
                    $mappedDocuments[] = $this->getDocument($indexType, $entityType, $id, $terms);
                }

                foreach (array_chunk($mappedDocuments, self::BATCH_SIZE) as &$batch) {
                    $this->searchClient->index($indexName, $batch);
                }
            }
        }

        private function getIndexDefinition(IndexType $indexType) : array {
            return match($indexType) {
                IndexType::Composite => $this->indexQueryDefinitionFactory->createCompositeIndexDefinition(),
                IndexType::Photo => $this->indexQueryDefinitionFactory->createPhotoIndexDefinition()
            };
        }

        private function getIndexName(IndexType $indexType) : string {
            return match($indexType) {
                IndexType::Composite => $this->compositeIndexName,
                IndexType::Photo => $this->photoIndexName
            };
        }

        private function getDocument(IndexType $indexType, IndexableEntityType $entityType, string $id, mixed $content) : array {
            return match($indexType) {
                IndexType::Composite => $this->getDocumentForCompositeIndex($entityType, $id, $content),
                IndexType::Photo => $this->getDocumentForPhotoIndex($entityType, $id, $content)
            };
        }

        private function getDocumentForCompositeIndex(IndexableEntityType $entityType, string $id, array $terms) : array {
            $name = !empty($terms) ? $terms[0] : "";

            return array(
                "id" => $this->getEntityId($entityType, $id),
                "entity_type" => $entityType->value,
                "entity_id" => $id,
                "entity_name" => $name,
                "search_text" => implode(" ", array_unique($terms))
            );
        }

        private function getDocumentForPhotoIndex(IndexableEntityType $entityType, string $id, array $data) : array {
            return array(
                "id" => $this->getEntityId($entityType, $id),
                "entity_type" => $entityType->value,
                "photo_id" => $id,
                "embedding" => $data["embedding"],
                "place_id" => $data["placeId"],
                "trip_id" => $data["tripId"] ?? null,
                "album_id" => $data["albumId"],
                "is_place_highlight" => $data["isPlaceHighlight"],
                "is_place_main_highlight" => $data["isPlaceMainHighlight"]
            );
        }

        private function getEntityId(IndexableEntityType $entityType, string $id) : string {
            return $entityType->value . "_" . $id;
        }
    }
?>