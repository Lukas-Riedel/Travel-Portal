<?php
    namespace Core\Service\Monitoring;

    interface DataConsistencyMonitor {
        public function fetchDataConsistencyIssues() : array;
    }
?>