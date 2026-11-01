<?php
    namespace Core\Service\CarRental;
    
    use Core\Client\Database\DatabaseClient;

    class CarRentalMapper {

        private readonly DatabaseClient $databaseClient;

        public function __construct(DatabaseClient $databaseClient) {
            $this->databaseClient = $databaseClient;
        }

        public function selectCarRentalsForTrip(string $tripId) : array {
            $sql = <<<SQL
                SELECT *
                FROM car_rental_event
                WHERE trip_id = ?
                ORDER BY "start"
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSet(function($carRentalRow) {
                    return new CarRental($carRentalRow["model"], $carRentalRow["start"], $carRentalRow["end"]);
                });
        }

        public function selectTripIdsForCreatedCarRentalEvents(string $oldCarRentalEventTableName) : array {
            $sql = <<<SQL
                SELECT ncre.trip_id
                FROM car_rental_event ncre
                LEFT JOIN {$oldCarRentalEventTableName} ocre
                    ON ocre.id = ncre.id
                WHERE ocre.model IS NULL
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdsForUpdatedCarRentalEvents(string $oldCarRentalEventTableName) : array {
            $sql = <<<SQL
                SELECT ncre.trip_id
                FROM car_rental_event ncre
                INNER JOIN {$oldCarRentalEventTableName} ocre
                    ON ocre.id = ncre.id
                WHERE ocre.model <> ncre.model
                    OR ocre."start" <> ncre."start"
                    OR ocre."end" <> ncre."end"
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdsForDeletedCarRentalEvents(string $oldCarRentalEventTableName) : array {
            $sql = <<<SQL
                SELECT ocre.trip_id
                FROM {$oldCarRentalEventTableName} ocre
                LEFT JOIN car_rental_event ncre
                    ON ocre.id = ncre.id
                WHERE ncre.id IS NULL
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function insertCarRentalEvent(CarRental $carRental, string $eventId, ?string $tripId) : bool {
            $sql = <<<SQL
                INSERT INTO car_rental_event (
                    id,
                    model,
                    trip_id,
                    "start",
                    "end"
                )
                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($eventId, $carRental->getModel(), $tripId, $carRental->getStart(), $carRental->getEnd())
                ->execute() === 1;
        }

        public function deleteAllCarRentalEvents() : void {
            $sql = <<<SQL
                DELETE
                FROM car_rental_event
            SQL;

            $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }

        public function createCarRentalEventTemporaryTable(string $tableName) : void {
            $sql = <<<SQL
                DROP TABLE IF EXISTS {$tableName}
            SQL;
            
            $this->databaseClient
                ->statementBuilder($sql)
                ->execute();

            $sql = <<<SQL
                CREATE TEMPORARY TABLE {$tableName} AS
                    SELECT *
                    FROM car_rental_event
            SQL;
            
            $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }
    }
?>
