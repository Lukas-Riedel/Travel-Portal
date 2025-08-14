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

        // TODO: Integrate into the statistics cached value.
        private const STATISTICS_VALIDITY_INTERVAL = 900;
        private const STATISTICS_VALIDITY_CACHE_KEY_FORMAT = "StatisticsService:StatisticsValidity:%s:%s";

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
            return $this->getStatistics(StatisticsType::Overall, NULL);          
        }
        
        public function updateCategoryStatistics(CategoryIdentifier $categoryIdentifier) : void {
            $this->updateStatistics(StatisticsType::Category, 0, time(), $categoryIdentifier->getId(), $categoryIdentifier->getId());
            $this->eventPublisher->publishCategoryStatisticsUpdatedEvent($categoryIdentifier->getId());
        }
        
        public function updateYearStatistics(int $year) : void {
            $this->updateStatistics(StatisticsType::Year, $this->getBeginningOfYearTimestamp($year), min(time(), $this->getEndOfYearTimestamp($year)), NULL, $year);
            $this->eventPublisher->publishYearStatisticsUpdatedEvent($year);
        }

        public function updateTripStatistics(Trip $trip) : void {    
            $this->updateStatistics(StatisticsType::Trip, $trip->getStart(), min(time(), $trip->getEnd()), NULL, $trip->getId());
            $this->eventPublisher->publishTripStatisticsUpdatedEvent($trip->getId(), $trip->getYear());
        }

        public function updateOverallStatistics() : void {     
            $this->updateStatistics(StatisticsType::Overall, 0, time(), NULL, NULL);          
        }

        public function setStatisticsProviders(array $statisticsProviders) : void {
            $this->statisticsProviders = $statisticsProviders;
        }

        // TODO: Remove the categoryId parameter.
        private function updateStatistics(StatisticsType $statisticsType, int $start, int $end, ?string $categoryId, ?string $entityId) : void {
            $statisticsValidityCacheKey = sprintf(self::STATISTICS_VALIDITY_CACHE_KEY_FORMAT, $statisticsType->name, $entityId ?? "NULL");
            $cachedStatisticsValidity = $this->cacheClient->get($statisticsValidityCacheKey);            
            if ($cachedStatisticsValidity !== NULL) {
                // TODO: Extend the Scheduler functionality with an event replay option.
                $secondsSinceLastUpdate = time() - $cachedStatisticsValidity;
                $this->logger->debug("The statistics for the entity '{$statisticsType->name}:{$entityId}' were computed {$secondsSinceLastUpdate} seconds ago, skipping the update...",
                    array("statisticsType" => $statisticsType->name, "entityId" => $entityId));
                return;
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
            $this->cacheClient->set($statisticsValidityCacheKey, time(), self::STATISTICS_VALIDITY_INTERVAL);
        }
        
        private function getBeginningOfYearTimestamp(int $year) : int {
            return strtotime(sprintf(self::BEGINNING_OF_YEAR_DATE_FORMAT, $year));
        }
        
        private function getEndOfYearTimestamp(int $year) : int {
            return strtotime(sprintf(self::END_OF_YEAR_DATE_FORMAT, $year));
        }

        private function getStatistics(StatisticsType $statisticsType, ?string $entityId) {
            if ($statisticsType !== StatisticsType::Overall && $entityId === NULL) {
                throw new \InvalidArgumentException("The entity identifier is required.");
            }
            
            return $this->statisticsMapper->selectStatisticsRecords($statisticsType, $entityId);
        }
    }
?>