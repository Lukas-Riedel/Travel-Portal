<?php
    namespace Core\Service\Index;

    use Common\Client\Cache\CacheClient;
    use Core\Client\Search\SearchClient;
    use Core\Common\CommonConstants;
    use Core\Service\Clustering\ClusteringService;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Embedding\EmbeddingService;
    use Monolog\Logger;
    use Ramsey\Uuid\Uuid;

    class IndexService {
        
        private const STYLE_EMBEDDING_CACHE_KEY = "IndexService:StyleEmbedding";
        private const STYLE_EMBEDDING_CACHE_TTL = CommonConstants::ONE_WEEK_SECONDS;
        private const STYLE_EMBEDDING_SAMPLES_CACHE_THRESHOLD = 100;

        private const BATCH_SIZE = 1000;

        private readonly IndexQueryDefinitionFactory $indexQueryDefinitionFactory;
        private readonly ClusteringService $clusteringService;
        private readonly EmbeddingService $embeddingService;
        private readonly ConfigurationService $configurationService;    
        private readonly SearchClient $searchClient;
        private readonly CacheClient $distributedCacheClient;
        private readonly Logger $logger;

        private readonly string $compositeIndexName;
        private readonly string $photoIndexName;
        private readonly string $selectedPhotoCandidatesLimitCoefficient;
        private readonly string $clustersCountCoefficient;
        private readonly string $styleEmbeddingCoefficient;
        private readonly string $negativeEmbeddingCoefficient;

        private array $entityIndexers = array();

        public function __construct(ClusteringService $clusteringService, EmbeddingService $embeddingService, ConfigurationService $configurationService,
            SearchClient $searchClient, CacheClient $distributedCacheClient, Logger $logger, string $compositeIndexName, string $photoIndexName,
            string $selectedPhotoCandidatesLimitCoefficient, string $clustersCountCoefficient, string $styleEmbeddingCoefficient, string $negativeEmbeddingCoefficient) {
            $this->indexQueryDefinitionFactory = new IndexQueryDefinitionFactory();
            $this->clusteringService = $clusteringService;
            $this->embeddingService = $embeddingService;
            $this->configurationService = $configurationService;
            $this->searchClient = $searchClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->logger = $logger;
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
                $searchResults = array_merge($searchResults, array_map(fn($nn) => new SearchResult(IndexableEntityType::Photo, new SearchResult(IndexableEntityType::Place, null, $nn->getParentEntityId()), $nn->getEntityId()),
                    $this->getNearestNeighbourPhotoIds($this->embeddingService->getTextEmbedding($query), $limit, $limit * $this->selectedPhotoCandidatesLimitCoefficient, true, false, true)));
            }

            if (in_array(IndexableEntityType::Highlight, $allowedEntityTypes)) {
                $searchResults = array_merge($searchResults, array_map(fn($nn) => new SearchResult(IndexableEntityType::Highlight, new SearchResult(IndexableEntityType::Place, null, $nn->getParentEntityId()), $nn->getEntityId()),
                    $this->getNearestNeighbourHighlightIds($this->embeddingService->getTextEmbedding($query), $limit, $limit * $this->selectedPhotoCandidatesLimitCoefficient, true, false, true)));
            }

            // This only works because the composite index is currently not supposed to contain neither photos nor highlights.
            if (empty($searchResults)) {
                $searchResults = array_merge($searchResults, array_map(fn($searchEntry) => new SearchResult(IndexableEntityType::from($searchEntry->getData()["entity_type"]), null, $searchEntry->getData()["entity_id"]),
                $this->searchClient->search($this->compositeIndexName, $this->indexQueryDefinitionFactory->createCompositeIndexSearchQuery($query, $limit, $allowedEntityTypes))));
            }

            return $searchResults;
        }

        public function getNearestNeighbourHighlightIds(array $embedding, int $limit, int $neighboursCount, bool $highlightsOnly, bool $placeMainHighlightsOnly, bool $distinctPlacesOnly) : array {
            return $this->getNearestNeighbours("highlight_id", $embedding, $limit, $neighboursCount, $highlightsOnly, $placeMainHighlightsOnly, $distinctPlacesOnly);
        }

        public function getNearestNeighbourPhotoIds(array $embedding, int $limit, int $neighboursCount, bool $highlightsOnly, bool $placeMainHighlightsOnly, bool $distinctPlacesOnly) : array {
            return $this->getNearestNeighbours("photo_id", $embedding, $limit, $neighboursCount, $highlightsOnly, $placeMainHighlightsOnly, $distinctPlacesOnly);
        }

        public function getSelectedPhotoIdsForPlace(string $placeId, string $query, int $count, ?string $mainHighlightPhotoId) : array {
            return $this->doGetSelectedPhotoIds($query, $count, $mainHighlightPhotoId, array(),
                fn($embedding) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, $count * $this->selectedPhotoCandidatesLimitCoefficient, null, array($placeId), array(), array(), null, null),
                fn($embedding, $prioritizedPhotoIds) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, count($prioritizedPhotoIds), null, array($placeId), array(), $prioritizedPhotoIds, null, null));
        }

        public function getSelectedPhotoIdsForTrip(string $tripId, string $query, int $count, ?string $mainHighlightPhotoId, array $tripPlaceHighlightPhotoIds) : array {
            return $this->doGetSelectedPhotoIds($query, $count, $mainHighlightPhotoId, $tripPlaceHighlightPhotoIds,
                fn($embedding) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, $count * $this->selectedPhotoCandidatesLimitCoefficient, null, array(), array($tripId), array(), null, null),
                fn($embedding, $prioritizedPhotoIds) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, count($prioritizedPhotoIds), null, array(), array($tripId), $prioritizedPhotoIds, null, null));            
        }

        public function getSelectedPhotoIdsForCategory(array $categoryPlaceIds, string $query, int $count, ?string $mainHighlightPhotoId, array $placeMainHighlightPhotoIds) : array {
            return $this->doGetSelectedPhotoIds($query, $count, $mainHighlightPhotoId, $placeMainHighlightPhotoIds,
                fn($embedding) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, $count * $this->selectedPhotoCandidatesLimitCoefficient, null, $categoryPlaceIds, array(), array(), true, null),
                fn($embedding, $prioritizedPhotoIds) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, count($prioritizedPhotoIds), null, $categoryPlaceIds, array(), $prioritizedPhotoIds, null, null));
        }

        public function getSelectedPhotoIdsForYear(int $year, string $query, int $count, ?string $mainHighlightPhotoId, array $tripMainHighlightPhotoIds) : array {
            return $this->doGetSelectedPhotoIds($query, $count, $mainHighlightPhotoId, $tripMainHighlightPhotoIds,
                fn($embedding) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, $count * $this->selectedPhotoCandidatesLimitCoefficient, $year, array(), array(), array(), null, true),
                fn($embedding, $prioritizedPhotoIds) => $this->indexQueryDefinitionFactory->createPhotoSelectionQuery(
                    $embedding, count($prioritizedPhotoIds), $year, array(), array(), $prioritizedPhotoIds, null, null));
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
            $start = microtime(true);
            if ($entityId !== null) {
                $this->logger->debug("Reindexing the '" . $entityId . "' " . $entityType->value . "...");
            }
            foreach ($this->entityIndexers as &$entityIndexer) {
                $documentBuffer = new DocumentBuffer($this->searchClient, $indexType, $entityType, $indexName, self::BATCH_SIZE);
                $entityIndexer->index($documentBuffer, $indexType, $entityType, $entityId);
                $documentBuffer->flush();
            }
            if ($entityId !== null) {
                $this->logger->info("The '" . $entityId . "' " . $entityType->value . " was reindexed in " . round((microtime(true) - $start) * 1000) . " milliseconds.");
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
            ?array $prioritizedPhotoIds, callable $entriesQuerySupplier, callable $prioritizedEntriesQuerySupplier) : array {            
            $combinedEmbedding = $this->computeEmbeddingForPhotoSelection($query);
            $searchEntries = $this->searchClient->search($this->photoIndexName, $entriesQuerySupplier($combinedEmbedding));

            $allPrioritizedPhotoIds = array();
            if ($mainHighlightPhotoId !== null) {
                $allPrioritizedPhotoIds[] = $mainHighlightPhotoId;
            }
            if ($prioritizedPhotoIds !== null) {
                $allPrioritizedPhotoIds = array_merge($allPrioritizedPhotoIds, $prioritizedPhotoIds);
            }

            if (!empty($allPrioritizedPhotoIds)) {
                $prioritizedSearchEntries = $this->searchClient->search($this->photoIndexName,
                        $prioritizedEntriesQuerySupplier($combinedEmbedding, $allPrioritizedPhotoIds));
                
                $existingSearchEntries = array_flip(array_map(fn($searchEntry) => $searchEntry->getData()["photo_id"], $searchEntries));
                foreach ($prioritizedSearchEntries as &$prioritizedSearchEntry) {
                    if (!isset($existingSearchEntries[$prioritizedSearchEntry->getData()["photo_id"]])) {
                        $searchEntries[] = $prioritizedSearchEntry;
                    }
                }
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
            $cachedStyleEmbedding = $this->distributedCacheClient->get(self::STYLE_EMBEDDING_CACHE_KEY);
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
                $this->distributedCacheClient->set(self::STYLE_EMBEDDING_CACHE_KEY, $result, self::STYLE_EMBEDDING_CACHE_TTL);
            }

            return $result;
        }

        private function getNegativeEmbedding() : array {
            $negativeTerms = $this->configurationService->getConfigurationEntry("embeddings")["negativeTerms"];
            return $this->embeddingService->getTextEmbedding(implode(", ", $negativeTerms));
        }
        
        private function getNearestNeighbours(string $propertyName, array $embedding, int $limit, int $neighboursCount, bool $highlightsOnly, bool $placeMainHighlightsOnly, bool $distinctPlacesOnly) : array {
            $searchEntries = $this->searchClient->search($this->photoIndexName,
                $this->indexQueryDefinitionFactory->createPhotoNearestNeighbourQuery($embedding, $limit, $neighboursCount, $highlightsOnly, $placeMainHighlightsOnly, $distinctPlacesOnly));

            return array_map(fn($searchEntry) => new NearestNeighbour($searchEntry->getData()[$propertyName], $searchEntry->getData()["place_id"], $searchEntry->getScore()), $searchEntries);
        }
    }
?>