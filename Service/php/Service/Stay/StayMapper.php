<?php
    namespace Service\Service\Stay;

    use Service\Service\Statistics\KeyValuePair;

    class StayMapper {

        private readonly \DatabaseProvider $databaseProvider;

        public function __construct(\DatabaseProvider $databaseProvider) {
            $this->databaseProvider = $databaseProvider;
        }

        public function selectTotalNightsCount(int $start, int $end) : int {
            $sql = <<<'SQL'
                SELECT SUM(ROUND((end - start) / 86400) - 1) AS total_nights
                FROM stay_event
                WHERE start >= ?
                    AND end <= ?
            SQL;

            return intval($this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getSingleColumn("total_nights"));
        }

        public function selectAverageNightsCountPerHotel(int $start, int $end) : int {
            $sql = <<<'SQL'
                SELECT ROUND(AVG(ROUND((end - start) / 86400) - 1)) AS average_nights
                FROM stay_event
                WHERE start >= ?
                    AND end <= ?
            SQL;

            return intval($this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getSingleColumn("average_nights"));
        }

        public function selectLongestStays(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT name,
                    ROUND((end - start) / 86400) - 1 AS nights
                FROM stay_event
                WHERE start >= ?
                    AND end <= ?
                ORDER BY ROUND((start - end) / 86400)
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getMappedResultSet(function($stayRow) {
                    return new KeyValuePair($stayRow["name"], $stayRow["nights"]);
                });
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