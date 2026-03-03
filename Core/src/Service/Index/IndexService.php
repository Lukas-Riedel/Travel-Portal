<?php
    namespace Core\Service\Index;

    use Common\Client\Cache\CacheClient;
    use Core\Client\Search\SearchClient;
    use Core\Common\CommonConstants;
    use Core\Service\Clustering\ClusteringService;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Embedding\EmbeddingService;
    use Ramsey\Uuid\Uuid;

    class IndexService {
        
        private const STYLE_EMBEDDING_CACHE_KEY = "IndexService:StyleEmbedding";
        private const STYLE_EMBEDDING_CACHE_TTL = CommonConstants::ONE_WEEK_SECONDS;
        private const STYLE_EMBEDDING_SAMPLES_CACHE_THRESHOLD = 100;
        
        private const NEGATIVE_EMBEDDING_CACHE_KEY_FORMAT = "IndexService:NegativeEmbedding:%s";
        private const NEGATIVE_EMBEDDING_CACHE_TTL = CommonConstants::ONE_MONTH_SECONDS;

        private const BATCH_SIZE = 1000;

        private readonly IndexQueryDefinitionFactory $indexQueryDefinitionFactory;

        private readonly ClusteringService $clusteringService;
        private readonly EmbeddingService $embeddingService;
        private readonly ConfigurationService $configurationService;
    
        private readonly SearchClient $searchClient;
        private readonly CacheClient $cacheClient;

        private readonly string $compositeIndexName;
        private readonly string $photoIndexName;

        private readonly string $selectedPhotoCandidatesLimitCoefficient;
        private readonly string $clustersCountCoefficient;
        private readonly string $styleEmbeddingCoefficient;
        private readonly string $negativeEmbeddingCoefficient;

        private array $entityIndexers = array();

        public function __construct(ClusteringService $clusteringService, EmbeddingService $embeddingService, ConfigurationService $configurationService,
            SearchClient $searchClient, CacheClient $cacheClient, string $compositeIndexName, string $photoIndexName, string $selectedPhotoCandidatesLimitCoefficient,
            string $clustersCountCoefficient, string $styleEmbeddingCoefficient, string $negativeEmbeddingCoefficient) {
            $this->indexQueryDefinitionFactory = new IndexQueryDefinitionFactory();
            $this->clusteringService = $clusteringService;
            $this->embeddingService = $embeddingService;
            $this->configurationService = $configurationService;
            $this->searchClient = $searchClient;
            $this->cacheClient = $cacheClient;
            $this->compositeIndexName = $compositeIndexName;
            $this->photoIndexName = $photoIndexName;
            $this->selectedPhotoCandidatesLimitCoefficient = $selectedPhotoCandidatesLimitCoefficient;
            $this->clustersCountCoefficient = $clustersCountCoefficient;
            $this->styleEmbeddingCoefficient = $styleEmbeddingCoefficient;
            $this->negativeEmbeddingCoefficient = $negativeEmbeddingCoefficient;
        }

        public function setEntityIndexers(array $entityIndexers) : void {
            $this->entityIndexers = $entityIndexers;
        }

        // TODO: This doesn't really respect the limit (but UI currently doesn't call it in a way that it matters).
        public function search(string $query, int $limit, array $allowedEntityTypes) : array {
            $searchResults = array();

            if (in_array(IndexableEntityType::Photo, $allowedEntityTypes)) {
                $searchResults = array_merge($searchResults, array_map(fn($nn) => new SearchResult(IndexableEntityType::Photo, $nn->getEntityId()), $this->getNearestNeighbourPhotoIds($this->embeddingService->getTextEmbedding($query), $limit, true, false)));
            }

            if (in_array(IndexableEntityType::Highlight, $allowedEntityTypes)) {
                $searchResults = array_merge($searchResults, array_map(fn($nn) => new SearchResult(IndexableEntityType::Highlight, $nn->getEntityId()), $this->getNearestNeighbourHighlightIds($this->embeddingService->getTextEmbedding($query), $limit, true, false)));
            }

            // This only works because the composite index is currently not supposed to contain neither photos nor highlights.
            if (empty($searchResults)) {
                $searchResults = array_merge($searchResults, array_map(fn($searchEntry) => new SearchResult(IndexableEntityType::from($searchEntry->getData()["entity_type"]), $searchEntry->getData()["entity_id"]),
                $this->searchClient->search($this->compositeIndexName, $this->indexQueryDefinitionFactory->createCompositeIndexSearchQuery($query, $limit, $allowedEntityTypes))));
            }

            return $searchResults;
        }

        public function getNearestNeighbourHighlightIds(array $embedding, int $limit, bool $highlightsOnly, bool $placeMainHighlightsOnly) : array {
            return $this->getNearestNeighbours("highlight_id", $embedding, $limit, $highlightsOnly, $placeMainHighlightsOnly);
        }

        public function getNearestNeighbourPhotoIds(array $embedding, int $limit, bool $highlightsOnly, bool $placeMainHighlightsOnly) : array {
            return $this->getNearestNeighbours("photo_id", $embedding, $limit, $highlightsOnly, $placeMainHighlightsOnly);
        }

        public function getSelectedPhotoIdsForPlace(string $placeId, string $query, int $count, ?string $mainHighlightPhotoId) : array {
            return $this->doGetSelectedPhotoIds($query, $count, $mainHighlightPhotoId, array(),
                fn($embedding) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, $count * $this->selectedPhotoCandidatesLimitCoefficient, array($placeId), array(), array(), null, null));
        }

        public function getSelectedPhotoIdsForTrip(string $tripId, string $query, int $count, ?string $mainHighlightPhotoId, array $tripPlaceHighlightPhotoIds) : array {
            return $this->doGetSelectedPhotoIds($query, $count, $mainHighlightPhotoId, $tripPlaceHighlightPhotoIds,
                fn($embedding) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, $count * $this->selectedPhotoCandidatesLimitCoefficient, array(), array($tripId), array(), null, null));            
        }

        public function getSelectedPhotoIdsForCategory(array $categoryPlaceIds, string $query, int $count, ?string $mainHighlightPhotoId, array $placeMainHighlightPhotoIds) : array {
            return $this->doGetSelectedPhotoIds($query, $count, $mainHighlightPhotoId, $placeMainHighlightPhotoIds,
                fn($embedding) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, $count * $this->selectedPhotoCandidatesLimitCoefficient, $categoryPlaceIds, array(), array(), true, null));
        }

        public function getSelectedPhotoIdsForYear(array $yearTripIds, string $query, int $count, ?string $mainHighlightPhotoId, array $tripMainHighlightPhotoIds) : array {
            return $this->doGetSelectedPhotoIds($query, $count, $mainHighlightPhotoId, $tripMainHighlightPhotoIds,
                fn($embedding) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, $count * $this->selectedPhotoCandidatesLimitCoefficient, array(), $yearTripIds, array(), null, true));
        }

        public function reindex() : void {           
            $usedIndexes = array(); 
            foreach (IndexType::cases() as &$indexType) {
                $temporaryIndexName = Uuid::uuid4()->toString();
                $this->searchClient->createIndex($temporaryIndexName, $this->getIndexDefinition($indexType));

                foreach (IndexableEntityType::cases() as &$entityType) {
                    $this->doIndex($temporaryIndexName, $indexType, $entityType, null);
                }

                $this->searchClient->reassignAlias($this->getIndexName($indexType), $temporaryIndexName);
                $usedIndexes[] = $temporaryIndexName;
            }

            $this->searchClient->deleteUnusedIndexes($usedIndexes);
        }

        public function index(IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void {
            $this->doIndex($this->getIndexName($indexType), $indexType, $entityType, $entityId);
        }

        private function doIndex(string $indexName, IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void {
            foreach ($this->entityIndexers as &$entityIndexer) {
                $documentBuffer = new DocumentBuffer($this->searchClient, $indexType, $entityType, $indexName, self::BATCH_SIZE);
                $entityIndexer->index($documentBuffer, $indexType, $entityType, $entityId);
                $documentBuffer->flush();
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

        private function doGetSelectedPhotoIds(string $query, int $count, ?string $mainHighlightPhotoId,
            ?array $prioritizedPhotoIds, callable $querySupplier) : array {            
            $combinedEmbedding = $this->computeEmbeddingForPhotoSelection($query);
            $searchEntries = $this->searchClient->search($this->photoIndexName, $querySupplier($combinedEmbedding));

            $allPrioritizedPhotoIds = array();
            if ($mainHighlightPhotoId !== null) {
                $allPrioritizedPhotoIds[] = $mainHighlightPhotoId;
            }
            if ($prioritizedPhotoIds !== null) {
                $allPrioritizedPhotoIds = array_merge($allPrioritizedPhotoIds, $prioritizedPhotoIds);
            }

            if (!empty($allPrioritizedPhotoIds)) {
                $prioritizedSearchEntries = $this->searchClient->search($this->photoIndexName,
                    $this->indexQueryDefinitionFactory->createPhotoSelectionQuery($combinedEmbedding, count($allPrioritizedPhotoIds), array(), array(), $allPrioritizedPhotoIds, null, null));
                
                $existingSearchEntries = array_flip(array_map(fn($searchEntry) => $searchEntry->getData()["photo_id"], $searchEntries));
                foreach ($prioritizedSearchEntries as &$prioritizedSearchEntry) {
                    if (!isset($existingSearchEntries[$prioritizedSearchEntry->getData()["photo_id"]])) {
                        $searchEntries[] = $prioritizedSearchEntry;
                    }
                }
            }

            if (empty($searchEntries)) {
                return array();
            }

            if (count($searchEntries) <= $count) {
                return array_map(fn($searchEntry) => $searchEntry->getData()["photo_id"], $searchEntries);
            }         
            
            $embeddings = array_map(fn($searchEntry) => $searchEntry->getData()["embedding"], $searchEntries);
            $clusters = $this->clusteringService->getEmbeddingsClusters($embeddings, round($count * $this->clustersCountCoefficient));

            $clustersMetadata = array();
            foreach ($clusters as $label => $indices) {
                foreach ($indices as &$idx) {
                    $clustersMetadata[$idx] = array(
                        "label" => $label,
                        "size" => count($indices)
                    );
                }
            }

            $prioritizedPhotoIdsMap = array_flip($prioritizedPhotoIds);
            $candidates = array_map(function($index, $entry) use (&$clustersMetadata, &$mainHighlightPhotoId, &$prioritizedPhotoIdsMap) {
                $photoId = $entry->getData()["photo_id"];
                $clusterMetadata = $clustersMetadata[$index];
                
                return array(
                    "index" => $index,
                    "photoId" => $photoId,
                    "clusterLabel" => $clusterMetadata["label"],
                    "priority" => $entry->getScore() * ($clusterMetadata["size"] ** 2),
                    "typeRank" => match(true) {
                        $photoId === $mainHighlightPhotoId => 0,
                        isset($prioritizedPhotoIdsMap[$photoId]) => 1,
                        default => 2
                    }
                );
            }, array_keys($searchEntries), $searchEntries);

            usort($candidates, fn($a, $b) => ($a["typeRank"] <=> $b["typeRank"]) ?: ($b["priority"] <=> $a["priority"]));

            $selectedPhotoIds = array();
            $usedClusterLabels = array();

            foreach ($candidates as &$candidate) {
                if (count($selectedPhotoIds) >= $count) {
                    break;
                }

                if (!isset($usedClusterLabels[$candidate["clusterLabel"]])) {
                    $selectedPhotoIds[$candidate["index"]] = $candidate["photoId"];
                    $usedClusterLabels[$candidate["clusterLabel"]] = true;
                }
            }

            foreach ($candidates as &$candidate) {
                if (count($selectedPhotoIds) >= $count) {
                    break;
                }

                if (!isset($selectedPhotoIds[$candidate["index"]])) {
                    $selectedPhotoIds[$candidate["index"]] = $candidate["photoId"];
                }
            }

            return array_values($selectedPhotoIds);
        }

        private function computeEmbeddingForPhotoSelection(string $query) : array {
            $contentEmbedding = $this->embeddingService->getTextEmbedding($query);
            $styleEmbedding = $this->getStyleEmbedding();
            $negativeEmbedding = $this->getNegativeEmbedding();

            $finalVector = array_map(fn($v, $n) => $v - ($n * $this->negativeEmbeddingCoefficient), $contentEmbedding, $negativeEmbedding);
            if ($styleEmbedding !== null) {
                $finalVector = array_map(fn($c, $s) => $c + ($s * $this->styleEmbeddingCoefficient), $finalVector, $styleEmbedding);
            }

            $norm = sqrt(array_sum(array_map(fn($v) => $v ** 2, $finalVector)));
            return $norm > 1e-10 ? array_map(fn($v) => $v / $norm, $finalVector) : $contentEmbedding;
        }

        private function getStyleEmbedding() : ?array {         
            $cachedStyleEmbedding = $this->cacheClient->get(self::STYLE_EMBEDDING_CACHE_KEY);
            if ($cachedStyleEmbedding !== null) {
                return $cachedStyleEmbedding;
            }

            $searchEntries = $this->searchClient->search($this->photoIndexName,
                $this->indexQueryDefinitionFactory->createAllPlaceMainHighlightsEmbeddingQuery());
            if (empty($searchEntries)) {
                return null;
            }

            $embeddings = array_map(fn($e) => $e->getData()["embedding"], $searchEntries);

            $sumVector = array_reduce($embeddings, function($carry, $vec) {
                return $carry ? array_map(fn($a, $b) => $a + $b, $carry, $vec) : $vec;
            }, array_fill(0, count($embeddings[0]), 0.0));

            $avgVector = array_map(fn($val) => $val / count($embeddings), $sumVector);
            $norm = sqrt(array_sum(array_map(fn($val) => $val ** 2, $avgVector)));

            $result = $norm > 1e-10 ? array_map(fn($val) => $val / $norm, $avgVector) : null;
            if (count($searchEntries) > self::STYLE_EMBEDDING_SAMPLES_CACHE_THRESHOLD) {
                $this->cacheClient->set(self::STYLE_EMBEDDING_CACHE_KEY, $result, self::STYLE_EMBEDDING_CACHE_TTL);
            }
            return $result;
        }

        private function getNegativeEmbedding() : array {
            $negativeTerms = $this->configurationService->getConfigurationEntry("embeddings")["negativeTerms"];
            $cacheKey = sprintf(self::NEGATIVE_EMBEDDING_CACHE_KEY_FORMAT, hash("sha256", json_encode($negativeTerms)));
            $cachedNegativeEmbedding = $this->cacheClient->get($cacheKey);
            if ($cachedNegativeEmbedding !== null) {
                return $cachedNegativeEmbedding;
            }

            $result = $this->embeddingService->getTextEmbedding(implode(", ", $negativeTerms));
            $this->cacheClient->set($cacheKey, $result, self::NEGATIVE_EMBEDDING_CACHE_TTL);
            return $result;
        }
        
        private function getNearestNeighbours(string $propertyName, array $embedding, int $limit, bool $highlightsOnly, bool $placeMainHighlightsOnly) : array {
            $searchEntries = $this->searchClient->search($this->photoIndexName,
                $this->indexQueryDefinitionFactory->createPhotoNearestNeighbourQuery($embedding, $limit, $highlightsOnly, $placeMainHighlightsOnly));

            return array_map(fn($searchEntry) => new NearestNeighbour($searchEntry->getData()[$propertyName], $searchEntry->getScore()), $searchEntries);
        }
    }
?>