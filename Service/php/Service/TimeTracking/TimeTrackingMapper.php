<?php
    namespace Service\Service\TimeTracking;

    class TimeTrackingMapper {
        
        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectTimeTrackingEvents(?string $type) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM tracking
                WHERE :CONDITIONS
                ORDER BY timestamp DESC,
                    id DESC
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder();
            if ($type !== NULL) {
                $whereClauseBuilder->withClause("type = ?", $type);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseProvider
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
            
            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()->withClause("timestamp <= ?", $timestamp);
            if ($type !== NULL) {
                $whereClauseBuilder->withClause("type = ?", $type);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseProvider
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

            return $this->databaseProvider
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

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($timeTrackingEvent->getType()->value, $timeTrackingEvent->getHours(),
                    $timeTrackingEvent->getDescription(), $timeTrackingEvent->getTimestamp())
                ->execute();
                 

            if ($wasInserted) {
                $timeTrackingEvent->setId($this->databaseProvider->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function deleteTimeTrackingEvent(string $eventId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM tracking
                WHERE id = ?
            SQL;

            return $this->databaseProvider
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

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($type)
                ->execute();
        }
    }
?>