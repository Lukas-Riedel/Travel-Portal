<?php
    class LoggingProvider {

        private $databaseProvider;

        public function __construct($databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function logError($message) {
            $this->databaseProvider
                ->statementBuilder("INSERT INTO cache_log (timestamp, message) VALUES (UNIX_TIMESTAMP(), ?)")
                ->withParameters($message)
                ->execute();
        }
    }
?>