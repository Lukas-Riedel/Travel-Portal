<?php
    namespace Service\Service\Monitoring;

    use Service\Client\CacheClient;

    class MonitoringService {
        
        private const DATA_CONSISTENCY_ISSUES_CACHE_KEY = "MonitoringService:DataConsistencyIssues";
        private const DATA_CONSISTENCY_ISSUES_CACHE_TTL = 86400;

        private readonly CacheClient $cacheClient;

        private array $dataConsistencyMonitors = array();

        public function __construct(CacheClient $cacheClient) {
            $this->cacheClient = $cacheClient;
        }

        public function setDataConsistencyMonitors(array $dataConsistencyMonitors) : void {
            $this->dataConsistencyMonitors = $dataConsistencyMonitors;
        }

        public function getDataConsistencyIssues() : array {
            $rawIssues = $this->cacheClient->get(self::DATA_CONSISTENCY_ISSUES_CACHE_KEY);
            if ($rawIssues === NULL || !is_array($rawIssues)) {
                return array();
            }

            return array_map(fn($dataConsistencyIssue) => new DataConsistencyIssue($dataConsistencyIssue["name"], $dataConsistencyIssue["context"], $dataConsistencyIssue["timestamp"]), $rawIssues);
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