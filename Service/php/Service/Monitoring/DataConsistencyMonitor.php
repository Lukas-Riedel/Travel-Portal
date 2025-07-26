<?php
    namespace Service\Service\Monitoring;

    interface DataConsistencyMonitor {
        public function fetchDataConsistencyIssues() : array;
    }
?>