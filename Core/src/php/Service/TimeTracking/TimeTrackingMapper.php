<?php
    namespace Core\Service\TimeTracking;

    use Core\Common\CommonConstants;
    use Core\Client\Database\DatabaseClient;

    class TimeTrackingMapper {

        private const STALE_USED_OVERTIME_THRESHOLD_SECONDS = 3 * CommonConstants::ONE_MONTH_SECONDS;
        
        private readonly DatabaseClient $databaseClient;

        public function __construct(DatabaseClient $databaseClient) {
            $this->databaseClient = $databaseClient;
        }

        public function selectTimeTrackingEvents(?string $type) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM tracking
                WHERE :CONDITIONS
                ORDER BY timestamp DESC,
                    id DESC
            SQL;

            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder();
            if ($type !== null) {
                $whereClauseBuilder->withClause("type = ?", $type);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($trackingEventRow) use(&$type) {
                    return new TimeTrackingEvent($trackingEventRow["id"], $trackingEventRow["description"], floatval($trackingEventRow["hours"]),
                        $trackingEventRow["timestamp"], TimeTrackingEventType::from($trackingEventRow["type"]),
                        $this->selectBalance($type, $trackingEventRow["timestamp"]));
                });
        }

        public function selectBalance(?string $type, int $timestamp) : float {
            $sql = <<<'SQL'
                SELECT COALESCE(SUM(hours), 0) AS balance 
                FROM tracking 
                WHERE :CONDITIONS
            SQL;
            
            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder()->withClause("timestamp <= ?", $timestamp);
            if ($type !== null) {
                $whereClauseBuilder->withClause("type = ?", $type);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getSingleColumn("balance");
        }

        public function selectCarryOverBalanceFromPreviousYears(string $type) : float {
            $sql = <<<'SQL'
                SELECT COALESCE(SUM(hours), 0) AS balance
                FROM tracking
                WHERE type = ?
                    AND YEAR(FROM_UNIXTIME(timestamp)) < YEAR(FROM_UNIXTIME(UNIX_TIMESTAMP()))
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($type)
                ->getSingleColumn("balance");
        }

        public function insertTimeTrackingEvent(TimeTrackingEvent $timeTrackingEvent) : bool {
            $sql = <<<'SQL'
                INSERT INTO tracking (
                    type, 
                    hours, 
                    description, 
                    timestamp
                ) 
                VALUES (
                    ?, 
                    ?, 
                    ?, 
                    ?
                )
            SQL;

            $wasInserted = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($timeTrackingEvent->getType()->value, $timeTrackingEvent->getHours(),
                    $timeTrackingEvent->getDescription(), $timeTrackingEvent->getTimestamp())
                ->execute();
                 

            if ($wasInserted) {
                $timeTrackingEvent->setId($this->databaseClient->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function deleteTimeTrackingEvent(string $eventId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM tracking
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($eventId)
                ->execute();
        }

        public function deleteTimeTrackingEventsFromPreviousYears(string $type) : int {
            $sql = <<<'SQL'
                DELETE
                FROM tracking
                WHERE type = ?
                    AND YEAR(FROM_UNIXTIME(timestamp)) < YEAR(FROM_UNIXTIME(UNIX_TIMESTAMP()))
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($type)
                ->execute();
        }

        public function deleteStalePlannedWorkEvents() : int {
            $sql = <<<'SQL'
                DELETE
                FROM tracking
                WHERE type = ?
                    AND timestamp <= (UNIX_TIMESTAMP() - 86400)
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters(TimeTrackingEventType::PlannedWork->value)
                ->execute();
        }

        public function deleteUsedOvertimeEvents() : int {
            $sql = <<<'SQL'
                DELETE 
                FROM tracking 
                WHERE timestamp <= (
                    SELECT COALESCE(MAX(t1.timestamp), 0) 
                    FROM tracking t1 
                    WHERE timestamp < UNIX_TIMESTAMP() - ? 
                        AND (
                            SELECT SUM(t2.hours) 
                            FROM tracking t2 
                            WHERE t2.timestamp <= t1.timestamp 
                                AND type = ?
                            ) <= 0 
                        AND t1.type = ?
                    ) 
                    AND type = ?
            SQL;
            
            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters(self::STALE_USED_OVERTIME_THRESHOLD_SECONDS, 
                    TimeTrackingEventType::Overtime->value, TimeTrackingEventType::Overtime->value, TimeTrackingEventType::Overtime->value)
                ->execute();
        }
    }
?>