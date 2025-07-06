<?php
    namespace Service\Service\Flight;

    use Service\Service\Category\CategoryService;
    use Service\Service\Geocoding\GeocodingService;

    class FlightMapper {

        private readonly \DatabaseProvider $databaseProvider;

        private readonly CategoryService $categoryService;

        private readonly GeocodingService $geocodingService;

        public function __construct(\DatabaseProvider $databaseProvider, CategoryService $categoryService,
            GeocodingService $geocodingService) {
            $this->databaseProvider = $databaseProvider;
            $this->categoryService = $categoryService;
            $this->geocodingService = $geocodingService;
        }

        public function selectTripIdsForCreatedFlightEvents(string $oldFlightEventTableName) : array {
            $sql = <<<SQL
                SELECT nfe.trip_id
                FROM flight_event nfe
                LEFT JOIN {$oldFlightEventTableName} ofe
                    ON ofe.id = nfe.id
                WHERE ofe.flight IS NULL
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdsForUpdatedFlightEvents(string $oldFlightEventTableName) : array {
            $sql = <<<SQL
                SELECT nfe.trip_id
                FROM flight_event nfe
                INNER JOIN {$oldFlightEventTableName} ofe
                    ON ofe.id = nfe.id
                WHERE ofe.flight <> nfe.flight
                    OR ofe.from <> nfe.from
                    OR ofe.to <> nfe.to
                    OR ofe.start <> nfe.start
                    OR ofe.end <> nfe.end
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdsForDeletedFlightEvents(string $oldFlightEventTableName) : array {
            $sql = <<<SQL
                SELECT ofe.trip_id
                FROM {$oldFlightEventTableName} ofe
                LEFT JOIN flight_event nfe
                    ON ofe.id = nfe.id
                WHERE nfe.id IS NULL
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function selectAirportIdentifier(string $code) : ?AirportIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM airport_identifier
                WHERE code = ?
            SQL;

            $airportIdentifierRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($code)
                ->getSingleRow();

            return $airportIdentifierRow === NULL ? NULL : new AirportIdentifier($airportIdentifierRow["id"], $airportIdentifierRow["code"],
                $this->categoryService->getCategoryIdentifierById($airportIdentifierRow["country_category_id"])->getName(),
                $airportIdentifierRow["latitude"], $airportIdentifierRow["longitude"], $airportIdentifierRow["timezone"]);
        }

        public function selectFlightsForTrip(FlightType $flightType, string $tripId) : array {
            $sql = <<<SQL
                SELECT fe.flight,
                    fe.from, 
                    fe.to, 
                    COALESCE(fl.actual_departure, fe.start) AS start, 
                    COALESCE(fl.actual_arrival, fe.end) AS end, 
                    fl.registration, 
                    fl.aircraft, 
                    fl.from_airport_id, 
                    fl.to_airport_id,
                    IF(fl.actual_arrival IS NULL, NULL, fl.actual_arrival - fe.end) AS delay,
                    fai.code AS from_airport_code, 
                    fai.latitude AS from_airport_latitude, 
                    fai.longitude AS from_airport_longitude, 
                    fai.country_category_id AS from_airport_country_category_id, 
                    fai.timezone AS from_airport_timezone, 
                    tai.code AS to_airport_code, 
                    tai.latitude AS to_airport_latitude, 
                    tai.longitude AS to_airport_longitude, 
                    tai.country_category_id AS to_airport_country_category_id, 
                    tai.timezone AS to_airport_timezone 
                FROM {$flightType->getTableName()} fe 
                LEFT JOIN flight_log fl 
                    ON fe.flight = fl.flight 
                        AND fe.start = fl.scheduled_departure 
                LEFT JOIN airport_identifier fai 
                    ON fl.from_airport_id = fai.id
                LEFT JOIN airport_identifier tai 
                    ON fl.to_airport_id = tai.id 
                WHERE fe.trip_id = ?
                ORDER BY start
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSet(function($flightRow) {       
                    $distance = NULL;
                    if ($flightRow["from_airport_latitude"] != NULL && $flightRow["from_airport_longitude"] != NULL && $flightRow["to_airport_latitude"] != NULL && $flightRow["to_airport_longitude"] != NULL) {
                        $distance = $this->geocodingService->getDistance($flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"],
                            $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"]);
                    }             

                    $from = new Airport($flightRow["from_airport_id"], $flightRow["from"], $flightRow["from_airport_code"], 
                        $flightRow["from_airport_country_category_id"] === NULL ? NULL : $this->categoryService->getCategoryIdentifierById($flightRow["from_airport_country_category_id"])->getName(), 
                        $flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["from_airport_timezone"] === NULL ? $this->geocodingService
                        ->getLocation($flightRow["from"])->getTimezone() : $flightRow["from_airport_timezone"]);
                    $to = new Airport($flightRow["to_airport_id"], $flightRow["to"], $flightRow["to_airport_code"],
                        $flightRow["to_airport_country_category_id"] === NULL ? NULL : $this->categoryService->getCategoryIdentifierById($flightRow["to_airport_country_category_id"])->getName(), 
                        $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"], $flightRow["to_airport_timezone"] === NULL ? $this->geocodingService
                        ->getLocation($flightRow["to"])->getTimezone() : $flightRow["to_airport_timezone"]);

                    return new Flight($flightRow["flight"], $flightRow["registration"], $flightRow["aircraft"], $distance, $from, $to, $flightRow["start"], $flightRow["end"], $flightRow["delay"]);
                });
        }

        public function selectAirlineIdentifiers() : array {
            $sql = <<<SQL
                SELECT code,
                    name,
                    logo
                FROM airline_identifier
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getMappedResultSet(function($airlineIdentifierRow) {
                    return new AirlineIdentifier($airlineIdentifierRow["code"], $airlineIdentifierRow["name"], $airlineIdentifierRow["logo"]);
                });
        }

        public function selectAirlineIdentifier(string $code) : ?AirlineIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM airline_identifier
                WHERE code = ?
            SQL;

            $airlineIdentifierRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($code)
                ->getSingleRow();

            return $airlineIdentifierRow === NULL ? NULL : new AirlineIdentifier($airlineIdentifierRow["code"], $airlineIdentifierRow["name"], $airlineIdentifierRow["logo"]);
        }

        public function selectLoggedFlightsForInterval(int $start, int $end, FlightSortingStrategy $flightSortingStrategy) : array {
            $sql = <<<SQL
                SELECT fe.flight,
                    fe.from, 
                    fe.to, 
                    fl.actual_departure AS start, 
                    fl.actual_arrival AS end, 
                    fl.registration, 
                    fl.aircraft, 
                    fl.from_airport_id, 
                    fl.to_airport_id, 
                    fl.actual_arrival - fe.end AS delay,
                    fai.code AS from_airport_code, 
                    fai.latitude AS from_airport_latitude, 
                    fai.longitude AS from_airport_longitude, 
                    fai.country_category_id AS from_airport_country_category_id, 
                    fai.timezone AS from_airport_timezone, 
                    tai.code AS to_airport_code, 
                    tai.latitude AS to_airport_latitude, 
                    tai.longitude AS to_airport_longitude, 
                    tai.country_category_id AS to_airport_country_category_id, 
                    tai.timezone AS to_airport_timezone 
                FROM flight_event fe 
                INNER JOIN flight_log fl 
                    ON fe.flight = fl.flight 
                        AND fe.start = fl.scheduled_departure 
                INNER JOIN airport_identifier fai 
                    ON fl.from_airport_id = fai.id
                INNER JOIN airport_identifier tai 
                    ON fl.to_airport_id = tai.id 
                WHERE fl.scheduled_departure >= ?
                    AND fl.scheduled_arrival <= ?
                {$flightSortingStrategy->value}
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getMappedResultSet(function($flightRow) {
                    $distance = $this->geocodingService->getDistance($flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"]);
                    $from = new Airport($flightRow["from_airport_id"], $flightRow["from"], $flightRow["from_airport_code"], 
                        $flightRow["from_airport_country_category_id"] === NULL ? NULL : $this->categoryService->getCategoryIdentifierById($flightRow["from_airport_country_category_id"])->getName(), 
                        $flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["from_airport_timezone"]);
                    $to = new Airport($flightRow["to_airport_id"], $flightRow["to"], $flightRow["to_airport_code"],
                        $flightRow["to_airport_country_category_id"] === NULL ? NULL : $this->categoryService->getCategoryIdentifierById($flightRow["to_airport_country_category_id"])->getName(), 
                        $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"], $flightRow["to_airport_timezone"]);

                    return new Flight($flightRow["flight"], $flightRow["registration"], $flightRow["aircraft"], $distance, $from, $to, $flightRow["start"], $flightRow["end"], $flightRow["delay"]);
                });                 
        }

        public function selectAirportIdentifierById(string $airportId) : ?AirportIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM airport_identifier
                WHERE id = ?
            SQL;

            $airportIdentifierRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($airportId)
                ->getSingleRow();

            return $airportIdentifierRow === NULL ? NULL : new AirportIdentifier($airportIdentifierRow["id"], $airportIdentifierRow["code"],
                $this->categoryService->getCategoryIdentifierById($airportIdentifierRow["country_category_id"])->getName(),
                $airportIdentifierRow["latitude"], $airportIdentifierRow["longitude"], $airportIdentifierRow["timezone"]);
        }

        public function selectTripIdForFlight(Flight $flight) : string {
            $sql = <<<'SQL'
                SELECT trip_id
                FROM flight_event
                WHERE flight = ?
                    AND start = ?
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($flight->getFlight(), $flight->getStart())
                ->getSingleColumn("trip_id");
        }

        public function selectFirstNonLoggedFlight() : ?Flight {
            $sql = <<<'SQL'
                SELECT fe.*
                FROM flight_event fe
                LEFT JOIN flight_log fl 
                    ON fe.flight = fl.flight 
                    AND fe.start = fl.scheduled_departure
                WHERE fl.actual_arrival IS NULL
                ORDER BY fe.end ASC
                LIMIT 1
            SQL;       
            
            $flightRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->getSingleRow();

            if ($flightRow === NULL) {
                return NULL;
            }

            $from = new Airport(NULL, $flightRow["from"], NULL, NULL, NULL, NULL, NULL);
            $to = new Airport(NULL, $flightRow["to"], NULL, NULL, NULL, NULL, NULL);
            return new Flight($flightRow["flight"], NULL, NULL, NULL, $from, $to, $flightRow["start"], $flightRow["end"], NULL);
        }

        public function selectAverageFlightDelay() : int {
            $sql = <<<'SQL'
                SELECT AVG(actual_arrival - scheduled_arrival) AS average_delay
                FROM flight_log 
                WHERE actual_arrival - scheduled_arrival > 0
            SQL;

            return intval($this->databaseProvider
                ->statementBuilder($sql)
                ->getSingleColumn("average_delay"));
        }

        public function insertAirportIdentifier(AirportIdentifier $airportIdentifier) : bool {
            $sql = <<<'SQL'
                INSERT INTO airport_identifier (
                    code, 
                    country_category_id, 
                    latitude, 
                    longitude, 
                    timezone
                )
                VALUES (
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?
                )
            SQL;

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($airportIdentifier->getCode(), $this->categoryService->getOrCreateCountryCategoryIdentifier($airportIdentifier->getCountry())->getId(),
                    $airportIdentifier->getLatitude(), $airportIdentifier->getLongitude(), $airportIdentifier->getTimezone())
                ->execute();

            if ($wasInserted) {
                $airportIdentifier->setId($this->databaseProvider->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function insertFlight(Flight $flight, int $scheduledDeparture, int $scheduledArrival) : bool {
            $sql = <<<'SQL'
                INSERT INTO flight_log (
                    flight, 
                    registration, 
                    aircraft, 
                    from_airport_id, 
                    to_airport_id, 
                    scheduled_departure, 
                    actual_departure, 
                    scheduled_arrival, 
                    actual_arrival
                )
                VALUES (
                    ?, 
                    ?, 
                    ?, 
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
                ->withParameters($flight->getFlight(), $flight->getRegistration(), $flight->getAircraft(), $flight->getFrom()->getId(), 
                    $flight->getTo()->getId(), $scheduledDeparture, $flight->getStart(), $scheduledArrival, $flight->getEnd())
                ->execute() === 1;
        }

        public function insertFlightEvent(FlightType $flightType, Flight $flight, string $eventId, string $tripId) : bool {
            $sql = <<<SQL
                INSERT INTO {$flightType->getTableName()} (
                    id,
                    trip_id, 
                    flight, 
                    `from`, 
                    `to`, 
                    start, 
                    end
                )
                VALUES (
                    ?, 
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
                ->withParameters($eventId, $tripId, $flight->getFlight(), $flight->getFrom()->getName(),
                    $flight->getTo()->getName(), $flight->getStart(), $flight->getEnd())
                ->execute() === 1;
        }

        public function insertAirlineIdentifier(AirlineIdentifier $airlineIdentifier) : bool {
            $sql = <<<'SQL'
                INSERT INTO airline_identifier (
                    code,
                    name,
                    logo
                )
                VALUES (
                    ?, 
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($airlineIdentifier->getCode(), $airlineIdentifier->getName(), $airlineIdentifier->getLogo())
                ->execute() === 1;
        }

        public function updateAirlineName(string $airlineCode, string $name) : bool {
            $sql = <<<'SQL'
                UPDATE airline_identifier
                SET name = ?
                WHERE code = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($name, $airlineCode)
                ->execute() === 1;
        }

        public function updateAirlineLogo(string $airlineCode, string $logo) : bool {
            $sql = <<<'SQL'
                UPDATE airline_identifier
                SET logo = ?
                WHERE code = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($logo, $airlineCode)
                ->execute() === 1;
        }

        public function deleteLoggedFlight(string $flight, string $actualDeparture, string $actualArrival) : int {
            $sql = <<<'SQL'
                DELETE
                FROM flight_log
                WHERE flight = ?
                    AND actual_departure = ?
                    AND actual_arrival = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($flight, $actualDeparture, $actualArrival)
                ->execute();
        }

        public function deleteAllFlightEvents(FlightType $flightType) : int {
            $sql = <<<SQL
                DELETE
                FROM {$flightType->getTableName()}
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }

        public function createFlightEventTemporaryTable(string $tableName) : void {            
            $sql = <<<SQL
                DROP TEMPORARY TABLE IF EXISTS {$tableName}
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();    

            $sql = <<<SQL
                CREATE TEMPORARY TABLE {$tableName} AS
                    SELECT *
                    FROM flight_event
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }
    }
?>