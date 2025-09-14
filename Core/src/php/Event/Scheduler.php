<?php
    namespace Core\Event;

    use Core\Client\Database\DatabaseClient;
    use Core\Event\Event;
    use Core\Event\EventPublisher;

    class Scheduler {

        private readonly DatabaseClient $databaseClient;
        private readonly EventPublisher $eventPublisher;

        public function __construct(DatabaseClient $databaseClient, EventPublisher $eventPublisher) {
            $this->databaseClient = $databaseClient;
            $this->eventPublisher = $eventPublisher;
        }

        public function schedule() {
            $this->eventPublisher->publish(Event::SchedulerTriggered());
        }

        public function requestExecution(string $action, int $interval) : bool {
            $sql = <<<'SQL'
                UPDATE scheduler
                SET last_triggered = UNIX_TIMESTAMP()
                WHERE action = ?
                    AND last_triggered <= UNIX_TIMESTAMP() - ?
            SQL;

            $wasRequested = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($action, $interval)
                ->execute() === 1;

            if ($wasRequested) {
                return true;
            }

            $sql = <<<'SQL'
                SELECT 1
                FROM scheduler
                WHERE action = ?
            SQL;

            $actionExists = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($action)
                ->getSingleRow() !== null;
            
            if ($actionExists) {
                return false;
            }

            $sql = <<<'SQL'
                INSERT INTO scheduler (
                    action, 
                    last_triggered
                )
                VALUES (
                    ?,
                    UNIX_TIMESTAMP()
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($action)
                ->execute() === 1;
        }

        public function requestDynamicExecution(string $action, callable $intervalSelector) : bool {
            $sql = <<<'SQL'
                SELECT last_triggered
                FROM scheduler
                WHERE action = ?
            SQL;

            $lastTriggered = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($action)
                ->getSingleColumn("last_triggered");

            return $this->requestExecution($action, $intervalSelector($lastTriggered));
        }
    }
?>