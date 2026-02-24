<?php
    namespace Core\Service\Index;

    use Core\Client\Search\SearchClient;

    class IndexService {

        private const BATCH_SIZE = 500;
    
        private readonly SearchClient $searchClient;

        private readonly string $compositeIndexName;

        private array $entityIndexers = array();

        public function __construct(SearchClient $searchClient, string $compositeIndexName) {
            $this->searchClient = $searchClient;
            $this->compositeIndexName = $compositeIndexName;
        }

        public function setEntityIndexers(array $entityIndexers) : void {
            $this->entityIndexers = $entityIndexers;
        }

        public function index() : void {
            $this->searchClient->deleteIndex($this->compositeIndexName);
            $this->searchClient->createIndex($this->compositeIndexName);

            foreach (IndexableEntityType::cases() as &$entityType) {
                foreach ($this->entityIndexers as &$entityIndexer) {
                    $documents = $entityIndexer->index($entityType);

                    if (empty($documents)) {
                        continue;
                    }

                    $mappedDocuments = array();
                    foreach ($documents as $id => $terms) {
                        $mappedDocuments[] = array(
                            "id" => $this->getId($entityType, $id),
                            "entity_type" => $entityType->value,
                            "entity_id" => (string) $id,
                            "search_text" => implode(" ", array_unique($terms))
                        );
                    }

                    foreach (array_chunk($mappedDocuments, self::BATCH_SIZE) as &$batch) {
                        $this->searchClient->index($this->compositeIndexName, $batch);
                    }
                }
            }
        }

        private function getId(IndexableEntityType $entityType, string $id) : string {
            return $entityType->value . "_" . $id;
        }
    }
?>