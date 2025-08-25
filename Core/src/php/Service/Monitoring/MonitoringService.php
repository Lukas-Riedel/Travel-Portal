<?php
    namespace Core\Service\Monitoring;

    use Core\Client\CacheClient;
    use Monolog\Logger;

    class MonitoringService {
        
        private const DATA_CONSISTENCY_ISSUES_CACHE_KEY = "MonitoringService:DataConsistencyIssues";
        private const DATA_CONSISTENCY_ISSUES_CACHE_TTL = 365 * 86400;

        private readonly CacheClient $cacheClient;
        
        private readonly \EventPublisher $eventPublisher;

        private readonly Logger $logger;

        private array $dataConsistencyMonitors = array();

        public function __construct(CacheClient $cacheClient, \EventPublisher $eventPublisher, Logger $logger) {
            $this->cacheClient = $cacheClient;
            $this->eventPublisher = $eventPublisher;
            $this->logger = $logger;
        }

        public function setDataConsistencyMonitors(array $dataConsistencyMonitors) : void {
            $this->dataConsistencyMonitors = $dataConsistencyMonitors;
        }

        public function getDataConsistencyIssues() : array {
            $rawIssues = $this->cacheClient->get(self::DATA_CONSISTENCY_ISSUES_CACHE_KEY);
            if ($rawIssues !== null) {
                return array_map(fn($dataConsistencyIssue) => new DataConsistencyIssue($dataConsistencyIssue["name"],
                    $dataConsistencyIssue["context"], $dataConsistencyIssue["timestamp"]), $rawIssues);
            }

            $this->logger->warning("The data consistency issues are not available, triggering a data consistency scan...");

            $this->eventPublisher->publishDataConsistencyScanTriggeredEvent();

            return array();
        }

        public function fetchDataConsistencyIssues() : void {
            $dataConsistencyIssues = array();
            foreach ($this->dataConsistencyMonitors as &$dataConsistencyMonitor) {
                foreach ($dataConsistencyMonitor->fetchDataConsistencyIssues() as &$dataConsistencyIssue) {
                    $dataConsistencyIssues[] = $dataConsistencyIssue;
                }
            }
            
            $this->cacheClient->set(self::DATA_CONSISTENCY_ISSUES_CACHE_KEY, $dataConsistencyIssues, self::DATA_CONSISTENCY_ISSUES_CACHE_TTL);
        }
    }
?>