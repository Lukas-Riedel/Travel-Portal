<?php
    class Scheduler {

        private $databaseProvider;
        private $eventPublisher;

        public function __construct($databaseProvider, $eventPublisher) {
            $this->databaseProvider = $databaseProvider;
            $this->eventPublisher = $eventPublisher;
        }

        public function schedule() {
            $events = $this->databaseProvider
                ->statementBuilder("SELECT action, UNIX_TIMESTAMP() - last_triggered AS timeSinceLastExecution FROM scheduler")
                ->getResultSet();

            foreach ($events as &$event) {
                $this->eventPublisher->publishSchedulerTriggeredEvent($event["action"], $event["timeSinceLastExecution"]);
            }
        }

        public function recordEventsTriggered($action) {            
            $this->databaseProvider
                ->statementBuilder("UPDATE scheduler SET last_triggered = UNIX_TIMESTAMP() WHERE action = ?")
                ->withParameters($action)
                ->execute();
        }
    }
?>