<?php
    namespace Core\Service\Index;

    use Core\Client\Search\SearchClient;
    use Ramsey\Uuid\Uuid;

    class IndexService {

        private const BATCH_SIZE = 500;

        private readonly IndexQueryDefinitionFactory $indexQueryDefinitionFactory;
    
        private readonly SearchClient $searchClient;

        private readonly string $compositeIndexName;

        private array $entityIndexers = array();

        public function __construct(SearchClient $searchClient, string $compositeIndexName) {
            $this->indexQueryDefinitionFactory = new IndexQueryDefinitionFactory();
            $this->searchClient = $searchClient;
            $this->compositeIndexName = $compositeIndexName;
        }

        public function setEntityIndexers(array $entityIndexers) : void {
            $this->entityIndexers = $entityIndexers;
        }

        public function search(string $query, int $limit, array $allowedEntityTypes) : array {
            return array_map(fn($searchResult) => new SearchResult(IndexableEntityType::from($searchResult["entity_type"]), $searchResult["entity_id"]),
                $this->searchClient->search($this->compositeIndexName, $this->indexQueryDefinitionFactory->createCompositeIndexSearchQuery($query, $limit, $allowedEntityTypes)));
        }

        public function reindex() : void {
            $temporaryIndexName = Uuid::uuid4()->toString();
            $this->searchClient->createIndex($temporaryIndexName, $this->indexQueryDefinitionFactory->createCompositeIndexDefinition());

            foreach (IndexableEntityType::cases() as &$entityType) {
                $this->doIndex($temporaryIndexName, $entityType);
            }

            $this->searchClient->reassignAlias($this->compositeIndexName, $temporaryIndexName);
        }

        public function index(IndexableEntityType $entityType) : void {
            $this->doIndex($this->compositeIndexName, $entityType);
        }

        private function doIndex(string $index, IndexableEntityType $entityType) : void {
            foreach ($this->entityIndexers as &$entityIndexer) {
                $documents = $entityIndexer->index($entityType);

                if (empty($documents)) {
                    continue;
                }

                $mappedDocuments = array();
                foreach ($documents as $id => $terms) {
                    $name = !empty($terms) ? $terms[0] : "";

                    $mappedDocuments[] = array(
                        "id" => $this->getEntityId($entityType, $id),
                        "entity_type" => $entityType->value,
                        "entity_id" => (string) $id,
                        "entity_name" => $name,
                        "search_text" => implode(" ", array_unique($terms))
                    );
                }

                foreach (array_chunk($mappedDocuments, self::BATCH_SIZE) as &$batch) {
                    $this->searchClient->index($index, $batch);
                }
            }
        }

        private function getEntityId(IndexableEntityType $entityType, string $id) : string {
            return $entityType->value . "_" . $id;
        }
    }
?>