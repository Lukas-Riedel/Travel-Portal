<?php
    namespace Core\Service\Statistics;

    use Monolog\Logger;
    use Core\Client\CacheClient;
    use Core\Service\Category\CategoryIdentifier;
    use Core\Service\Trip\Trip;

    class StatisticsService {

        private const STATISTICS_VALUES_COUNT_LIMIT = 5;

        private const BEGINNING_OF_YEAR_DATE_FORMAT = "1/1/%s 12:00:00 AM";
        private const END_OF_YEAR_DATE_FORMAT = "12/31/%s 11:59:59 PM";

        private const STATISTICS_VALIDITY_SECONDS = 900;

        private const STATISTICS_COLLECTION_CACHE_KEY_FORMAT = "StatisticsService:StatisticsCollection:%s:%s";
        private const STATISTICS_COLLECTION_CACHE_TTL = 365 * 86400;

        // TODO: Remove after all statistics are re-computed and stored to Redis.
        private readonly StatisticsMapper $statisticsMapper;

        private readonly CacheClient $cacheClient;
        
        private readonly \EventPublisher $eventPublisher;

        private readonly Logger $logger;

        private array $statisticsProviders = array();

        public function __construct(\DatabaseProvider $databaseProvider, CacheClient $cacheClient, \EventPublisher $eventPublisher, Logger $logger) {
            $this->statisticsMapper = new StatisticsMapper($databaseProvider);
            $this->cacheClient = $cacheClient;
            $this->eventPublisher = $eventPublisher;
            $this->logger = $logger;
        }

        public function getCategoryStatistics(string $categoryId) : array {    
            return $this->getStatistics(StatisticsType::Category, $categoryId);
        }
        
        public function getYearStatistics(int $year) : array {     
            return $this->getStatistics(StatisticsType::Year, $year);
        }
        
        public function getTripStatistics(string $tripId) : array {         
            return $this->getStatistics(StatisticsType::Trip, $tripId);
        }

        public function getOverallStatistics() : array {     
            return $this->getStatistics(StatisticsType::Overall, null);          
        }
        
        public function updateCategoryStatistics(CategoryIdentifier $categoryIdentifier) : void {
            $this->updateStatistics(StatisticsType::Category, 0, time(), $categoryIdentifier->getId(), $categoryIdentifier->getId());
            $this->eventPublisher->publishCategoryStatisticsUpdatedEvent($categoryIdentifier->getId());
        }
        
        public function updateYearStatistics(int $year) : void {
            $this->updateStatistics(StatisticsType::Year, $this->getBeginningOfYearTimestamp($year), min(time(), $this->getEndOfYearTimestamp($year)), null, $year);
            $this->eventPublisher->publishYearStatisticsUpdatedEvent($year);
        }

        public function updateTripStatistics(Trip $trip) : void {    
            $this->updateStatistics(StatisticsType::Trip, $trip->getStart(), min(time(), $trip->getEnd()), null, $trip->getId());
            $this->eventPublisher->publishTripStatisticsUpdatedEvent($trip->getId(), $trip->getYear());
        }

        public function updateOverallStatistics() : void {     
            $this->updateStatistics(StatisticsType::Overall, 0, time(), null, null);          
        }

        public function setStatisticsProviders(array $statisticsProviders) : void {
            $this->statisticsProviders = $statisticsProviders;
        }

        // TODO: Remove the categoryId parameter.
        private function updateStatistics(StatisticsType $statisticsType, int $start, int $end, ?string $categoryId, ?string $entityId) : void {
            $statisticsCollectionCacheKey = $this->getStatisticsCollectionCacheKey($statisticsType, $entityId);
            $cachedStatisticsCollection = $this->cacheClient->get($statisticsCollectionCacheKey);            
            if ($cachedStatisticsCollection !== null) {
                $secondsSinceLastUpdate = time() - $cachedStatisticsCollection["timestamp"];

                // Do not compute the same statistics within a short interval to avoid flooding worker processes.
                if ($secondsSinceLastUpdate < self::STATISTICS_VALIDITY_SECONDS) {
                    // TODO: Extend the Scheduler functionality with an event replay option.
                    $this->logger->debug("The statistics for the entity '{$statisticsType->name}:{$entityId}' were computed {$secondsSinceLastUpdate} seconds ago, skipping the update...",
                        array("statisticsType" => $statisticsType->name, "entityId" => $entityId));
                    return;
                }
            }

            $updatedStatisticsRecords = array();

            foreach (StatisticsKind::cases() as &$statisticsKind) {
                foreach ($this->statisticsProviders as &$statisticsProvider) {
                    $fetchedStatisticsRecords = $statisticsProvider->fetchStatistics($statisticsType, $statisticsKind, $start, $end, $categoryId, $entityId);
                    foreach ($fetchedStatisticsRecords as &$fetchedStatisticsRecord) {
                        if ($fetchedStatisticsRecord->hasValue()) {
                            $updatedStatisticsRecords[] = $fetchedStatisticsRecord->withLimitedValuesCount(self::STATISTICS_VALUES_COUNT_LIMIT);
                        }
                    }
                }
            }
            
            $this->statisticsMapper->deleteAllStatisticsRecords($statisticsType, $entityId);
            foreach ($updatedStatisticsRecords as &$updatedStatisticsRecord) {
                $this->statisticsMapper->insertStatisticsRecord($statisticsType, $updatedStatisticsRecord, $entityId);
            }

            if (count($updatedStatisticsRecords) > 0) {
                $this->cacheClient->set($statisticsCollectionCacheKey, new StatisticsCollection($updatedStatisticsRecords, time()),
                    self::STATISTICS_COLLECTION_CACHE_TTL);
            }
        }
        
        private function getBeginningOfYearTimestamp(int $year) : int {
            return strtotime(sprintf(self::BEGINNING_OF_YEAR_DATE_FORMAT, $year));
        }
        
        private function getEndOfYearTimestamp(int $year) : int {
            return strtotime(sprintf(self::END_OF_YEAR_DATE_FORMAT, $year));
        }

        private function getStatistics(StatisticsType $statisticsType, ?string $entityId) : array {
            if ($statisticsType !== StatisticsType::Overall && $entityId === null) {
                throw new \InvalidArgumentException("The entity identifier is required.");
            }

            $statisticsCollectionCacheKey = $this->getStatisticsCollectionCacheKey($statisticsType, $entityId);
            $statisticsCollection = $this->cacheClient->get($statisticsCollectionCacheKey);
            if ($statisticsCollection !== null) {
                return $statisticsCollection["statistics"];
            }
            
            return $this->statisticsMapper->selectStatisticsRecords($statisticsType, $entityId);
        }

        private function getStatisticsCollectionCacheKey(StatisticsType $statisticsType, string $entityId) : string {
            return sprintf(self::STATISTICS_COLLECTION_CACHE_KEY_FORMAT, $statisticsType->name, $entityId ?? "null");
        }
    }
?>