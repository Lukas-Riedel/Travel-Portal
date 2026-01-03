<?php
    namespace Core\Event;

    use Core\Client\Cache\CacheClient;
    use Core\Client\Database\DatabaseClient;
    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;

    class Scheduler {

        private readonly DatabaseClient $databaseClient;
        private readonly CacheClient $distributedCacheClient;
        private readonly EventPublisher $eventPublisher;

        public function __construct(DatabaseClient $databaseClient, CacheClient $distributedCacheClient, EventPublisher $eventPublisher) {
            $this->databaseClient = $databaseClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->eventPublisher = $eventPublisher;
        }

        public function schedule() {
            $this->eventPublisher->publish(Event::SchedulerTriggered());

            $rawEvents = $this->distributedCacheClient->getSortedSet(CommonConstants::DELAYED_EVENTS_SORTED_SET_KEY)->remove(0, time());
            foreach ($rawEvents as &$rawEvent) {
                $this->eventPublisher->publishRawEvent($rawEvent["name"], $rawEvent["args"]);
            }
        }

        public function requestExecution(string $action, int $interval) : bool {
            $sql = <<<'SQL'
                UPDATE scheduler
                SET last_triggered = ROUND(EXTRACT(EPOCH FROM NOW()))
                WHERE action = ?
                    AND last_triggered <= ROUND(EXTRACT(EPOCH FROM NOW())) - ?
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
                    ROUND(EXTRACT(EPOCH FROM NOW()))
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