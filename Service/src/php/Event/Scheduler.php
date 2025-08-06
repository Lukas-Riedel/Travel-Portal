<?php
    class Scheduler {

        private $databaseProvider;
        private $eventPublisher;

        public function __construct($databaseProvider, $eventPublisher) {
            $this->databaseProvider = $databaseProvider;
            $this->eventPublisher = $eventPublisher;
        }

        public function schedule() {
            $actions = $this->databaseProvider
                ->statementBuilder("SELECT action AS name, last_triggered AS lastTriggered FROM scheduler")
                ->getResultSet();

            $this->eventPublisher->publishSchedulerTriggeredEvent($actions);
        }

        public function recordEventsTriggered($action) {            
            $this->databaseProvider
                ->statementBuilder("UPDATE scheduler SET last_triggered = UNIX_TIMESTAMP() WHERE action = ?")
                ->withParameters($action)
                ->execute();
        }
    }
?>