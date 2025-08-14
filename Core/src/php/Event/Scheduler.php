<?php
    class Scheduler {

        private readonly \DatabaseProvider $databaseProvider;
        private readonly \EventPublisher $eventPublisher;

        public function __construct(\DatabaseProvider $databaseProvider, \EventPublisher $eventPublisher) {
            $this->databaseProvider = $databaseProvider;
            $this->eventPublisher = $eventPublisher;
        }

        public function schedule() {
            $this->eventPublisher->publishSchedulerTriggeredEvent();
        }

        public function requestExecution(string $action, int $interval) : bool {
            // TODO: Insert the action if not persent.
            $sql = <<<'SQL'
                UPDATE scheduler
                SET last_triggered = UNIX_TIMESTAMP()
                WHERE action = ?
                    AND last_triggered <= UNIX_TIMESTAMP() - ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($action, $interval)
                ->execute() === 1;
        }

        public function requestDynamicExecution(string $action, callable $intervalSelector) : bool {
            $sql = <<<'SQL'
                SELECT last_triggered
                FROM scheduler
                WHERE action = ?
            SQL;

            $lastTriggered = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($action)
                ->getSingleColumn("last_triggered");

            return $this->requestExecution($action, $intervalSelector($lastTriggered));
        }
    }
?>