<?php
    namespace Common\Client;

    interface HealthCheckable {
        public function getServiceName() : string;
        public function isHealthy() : bool;
    }
?>