<?php
    namespace Core\Service\Statistics;

    use Monolog\Logger;
    use Core\Client\Cache\CacheClient;
    use Core\Common\CommonConstants;
    use Core\Service\Category\CategoryIdentifier;
    use Core\Service\Trip\Trip;
    use Core\Event\Event;
    use Core\Event\EventPublisher;

    class StatisticsService {

        private const STATISTICS_VALUES_COUNT_LIMIT = 5;

        private const BEGINNING_OF_YEAR_DATE_FORMAT = "1/1/%s 12:00:00 AM";
        private const END_OF_YEAR_DATE_FORMAT = "12/31/%s 11:59:59 PM";

        private const STATISTICS_VALIDITY_SECONDS = 3600;

        private const STATISTICS_COLLECTION_CACHE_KEY_FORMAT = "StatisticsService:StatisticsCollection:%s:%s";
        private const STATISTICS_COLLECTION_CACHE_TTL = CommonConstants::ONE_YEAR_SECONDS;

        private readonly CacheClient $cacheClient;
        
        private readonly EventPublisher $eventPublisher;

        private readonly Logger $logger;

        private array $statisticsProviders = array();

        public function __construct(CacheClient $cacheClient, EventPublisher $eventPublisher, Logger $logger) {
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
            $this->eventPublisher->publish(Event::CategoryStatisticsUpdated($categoryIdentifier->getId()));
        }
        
        public function updateYearStatistics(int $year) : void {
            $this->updateStatistics(StatisticsType::Year, $this->getBeginningOfYearTimestamp($year), min(time(), $this->getEndOfYearTimestamp($year)), null, $year);
            $this->eventPublisher->publish(Event::YearStatisticsUpdated($year));
        }

        public function updateTripStatistics(Trip $trip) : void {    
            $this->updateStatistics(StatisticsType::Trip, $trip->getStart(), min(time(), $trip->getEnd()), null, $trip->getId());
            $this->eventPublisher->publish(Event::TripStatisticsUpdated($trip->getId(), $trip->getYear()));
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
                    $this->logger->debug("The statistics for the entity '{$statisticsType->name}{$entityId}' were computed {$secondsSinceLastUpdate} seconds ago, skipping the update...");
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

            $this->logger->warning("The statistics for the entity '{$statisticsType->name}{$entityId}' are not available, scheduling an update...");

            switch ($statisticsType) {
                case StatisticsType::Overall:
                    $this->eventPublisher->publish(Event::OverallStatisticsInvalidated());
                    break;
                case StatisticsType::Trip:
                    $this->eventPublisher->publish(Event::TripStatisticsInvalidated($entityId));
                    break;
                case StatisticsType::Category:
                    $this->eventPublisher->publish(Event::CategoryStatisticsInvalidated($entityId));
                    break;
                case StatisticsType::Year:
                    $this->eventPublisher->publish(Event::YearStatisticsInvalidated($entityId));
                    break;
            }

            return array();
        }

        private function getStatisticsCollectionCacheKey(StatisticsType $statisticsType, ?string $entityId) : string {
            return sprintf(self::STATISTICS_COLLECTION_CACHE_KEY_FORMAT, $statisticsType->name, $entityId ?? "null");
        }
    }
?>