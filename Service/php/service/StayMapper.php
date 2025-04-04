<?php
    class StayMapper {

        private readonly DatabaseProvider $databaseProvider;

        public function __construct(DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectStaysForTrip(string $tripId) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM stay_event
                WHERE trip_id = ?
                ORDER BY start
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSet(function($stayRow) {
                    return new Stay($stayRow["name"], $stayRow["address"], $stayRow["start"], $stayRow["end"]);
                });
        }

        public function selectTripIdsForCreatedStayEvents(string $oldStayEventTableName) : array {
            $sql = <<<SQL
                SELECT nse.trip_id
                FROM stay_event nse
                LEFT JOIN {$oldStayEventTableName} ose
                    ON ose.id = nse.id
                WHERE ose.name IS NULL
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdsForUpdatedStayEvents(string $oldStayEventTableName) : array {
            $sql = <<<SQL
                SELECT nse.trip_id
                FROM stay_event nse
                INNER JOIN {$oldStayEventTableName} ose
                    ON ose.id = nse.id
                WHERE ose.name <> nse.name
                    OR ose.address <> nse.address
                    OR ose.start <> nse.start
                    OR ose.end <> nse.end
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdsForDeletedStayEvents(string $oldStayEventTableName) : array {
            $sql = <<<SQL
                SELECT ose.trip_id
                FROM {$oldStayEventTableName} ose
                LEFT JOIN stay_event nse
                    ON ose.id = nse.id
                WHERE nse.id IS NULL
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function insertStayEvent(Stay $stay, string $eventId, string $tripId) : bool {
            $sql = <<<'SQL'
                INSERT INTO stay_event (
                    id, 
                    name, 
                    trip_id, 
                    start, 
                    end, 
                    address
                )
                VALUES (
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($eventId, $stay->getName(), $tripId, $stay->getStart(), $stay->getEnd(), $stay->getAddress())
                ->execute() === 1;
        }

        public function deleteAllStayEvents() : void {
            $sql = <<<'SQL'
                DELETE FROM stay_event
            SQL;

            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }

        public function createStayEventTemporaryTable(string $tableName) : void {            
            $sql = <<<SQL
                DROP TEMPORARY TABLE IF EXISTS {$tableName}
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();    

            $sql = <<<SQL
                CREATE TEMPORARY TABLE {$tableName} AS
                    SELECT *
                    FROM stay_event
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }
    }
?>