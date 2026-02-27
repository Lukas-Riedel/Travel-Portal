<?php
    namespace Core\Service\Index;

    use Core\Client\Search\SearchClient;
    use Core\Service\Category\CategoryService;
    use Core\Service\Flight\FlightService;
    use Core\Service\Label\LabelService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Trip\TripService;
    use Core\Service\Year\YearService;
    use Ramsey\Uuid\Uuid;

    class IndexService {

        private const BATCH_SIZE = 500;

        private readonly IndexQueryDefinitionFactory $indexQueryDefinitionFactory;

        private readonly CategoryService $categoryService;
        private readonly PlaceService $placeService;
        private readonly FlightService $flightService;
        private readonly LabelService $labelService;
        private readonly TripService $tripService;
        private readonly YearService $yearService;
    
        private readonly SearchClient $searchClient;

        private readonly string $compositeIndexName;

        private array $entityIndexers = array();

        public function __construct(CategoryService $categoryService, PlaceService $placeService, FlightService $flightService, LabelService $labelService,
            TripService $tripService, YearService $yearService, SearchClient $searchClient, string $compositeIndexName) {
            $this->indexQueryDefinitionFactory = new IndexQueryDefinitionFactory();
            $this->categoryService = $categoryService;
            $this->placeService = $placeService;
            $this->flightService = $flightService;
            $this->labelService = $labelService;
            $this->tripService = $tripService;
            $this->yearService = $yearService;
            $this->searchClient = $searchClient;
            $this->compositeIndexName = $compositeIndexName;
        }

        public function setEntityIndexers(array $entityIndexers) : void {
            $this->entityIndexers = $entityIndexers;
        }

        public function search(string $query, int $limit, array $allowedEntityTypes) : array {
            $rawResults = $this->searchClient->search($this->compositeIndexName, $this->indexQueryDefinitionFactory->createCompositeIndexSearchQuery($query, $limit, $allowedEntityTypes));

            $results = array();
            foreach ($rawResults as &$rawResult) {
                $entityType = IndexableEntityType::from($rawResult["entity_type"]);
                $entity = $this->getEntity($entityType, $rawResult["entity_id"]);

                if ($entity !== null) {
                    $results[] = new SearchResult($entityType, $entity);
                }
            }

            return $results;
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

        private function getEntity(IndexableEntityType $entityType, string $entityId) : mixed {            
            return match ($entityType) {
                IndexableEntityType::Category => $this->categoryService->getCategoryIdentifierById($entityId),
                IndexableEntityType::Place => $this->placeService->getPlaceIdentifierById($entityId),
                IndexableEntityType::Airport => $this->flightService->getAirportIdentifier($entityId),
                IndexableEntityType::Airline => $this->flightService->getAirlineIdentifier($entityId),
                IndexableEntityType::Label => $this->labelService->getLabel($entityId),
                IndexableEntityType::Trip => $this->tripService->getTripIdentifierById($entityId),
                IndexableEntityType::Year => $this->yearService->getYearIdentifier($entityId)
            };
        }
    }
?>