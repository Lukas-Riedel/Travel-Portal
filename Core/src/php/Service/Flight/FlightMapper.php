<?php
    namespace Core\Service\Flight;

    use Core\Service\Category\CategoryService;
    use Core\Service\Geocoding\GeocodingService;

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

            if ($airportIdentifierRow === null) {
                return null;
            }

            return new AirportIdentifier($airportIdentifierRow["id"], $airportIdentifierRow["name"], $airportIdentifierRow["code"],
                $this->categoryService->getCategoryIdentifierById($airportIdentifierRow["country_category_id"])->getName(),
                $airportIdentifierRow["latitude"], $airportIdentifierRow["longitude"], $airportIdentifierRow["timezone"]);
        }

        public function selectAirlineCodeId(string $code) : ?string {
            $sql = <<<'SQL'
                SELECT id
                FROM airline_code
                WHERE code = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($code)
                ->getSingleColumn("id");
        }

        public function selectUnassignedAirlineCodes() : array {
            $sql = <<<'SQL'
                SELECT code
                FROM airline_code
                WHERE airline_id IS NULL
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("code");
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
                    ai.id AS airline_id,
                    ai.name AS airline_name,
                    fl.from_airport_id, 
                    fl.to_airport_id,
                    IF(fl.actual_arrival IS NULL, null, fl.actual_arrival - fl.scheduled_arrival) AS delay,
                    fai.name AS from_airport_name,
                    fai.code AS from_airport_code, 
                    fai.latitude AS from_airport_latitude, 
                    fai.longitude AS from_airport_longitude, 
                    fai.country_category_id AS from_airport_country_category_id, 
                    fai.timezone AS from_airport_timezone, 
                    tai.name AS to_airport_name,
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
                LEFT JOIN airline_code ac
                    ON fl.airline_code_id = ac.id
                LEFT JOIN airline_identifier ai
                    ON ac.airline_id = ai.id
                WHERE fe.trip_id = ?
                ORDER BY start
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSet(function($flightRow) {       
                    $distance = null;
                    if ($flightRow["from_airport_latitude"] != null && $flightRow["from_airport_longitude"] != null && $flightRow["to_airport_latitude"] != null && $flightRow["to_airport_longitude"] != null) {
                        $distance = $this->geocodingService->getDistance($flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"],
                            $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"]);
                    }             

                    $airlineIdentifier = $flightRow["airline_id"] === null || $flightRow["airline_name"] === null ? null : new AirlineIdentifier($flightRow["airline_id"], $flightRow["airline_name"]);
                    $from = new Airport($flightRow["from_airport_id"], $flightRow["from"], $flightRow["from_airport_name"], $flightRow["from_airport_code"], 
                        $flightRow["from_airport_country_category_id"] === null ? null : $this->categoryService->getCategoryIdentifierById($flightRow["from_airport_country_category_id"])->getName(), 
                        $flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["from_airport_timezone"] === null ? $this->geocodingService
                        ->getLocation($flightRow["from"])->getTimezone() : $flightRow["from_airport_timezone"]);
                    $to = new Airport($flightRow["to_airport_id"], $flightRow["to"], $flightRow["to_airport_name"], $flightRow["to_airport_code"],
                        $flightRow["to_airport_country_category_id"] === null ? null : $this->categoryService->getCategoryIdentifierById($flightRow["to_airport_country_category_id"])->getName(), 
                        $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"], $flightRow["to_airport_timezone"] === null ? $this->geocodingService
                        ->getLocation($flightRow["to"])->getTimezone() : $flightRow["to_airport_timezone"]);

                    return new Flight($flightRow["flight"], $flightRow["registration"], $flightRow["aircraft"], $airlineIdentifier, $distance, $from, $to, $flightRow["start"], $flightRow["end"], $flightRow["delay"]);
                });
        }

        public function selectAirlines() : array {
            $sql = <<<SQL
                SELECT ai.id,
                    ai.name,
                    ai.logo,
                    COALESCE(GROUP_CONCAT(ac.code SEPARATOR ','), '') AS codes
                FROM airline_identifier ai
                LEFT JOIN airline_code ac
                    ON ai.id = ac.airline_id
                GROUP BY ai.id
                ORDER BY ai.name
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getMappedResultSet(function($airlineRow) {
                    return new Airline($airlineRow["id"], $airlineRow["name"], explode(",", $airlineRow["codes"]), $airlineRow["logo"]);
                });
        }

        public function selectAirline(string $airlineId) : ?Airline {
            $sql = <<<'SQL'
                SELECT ai.id,
                    ai.name,
                    ai.logo,
                    COALESCE(GROUP_CONCAT(ac.code SEPARATOR ','), '') AS codes
                FROM airline_identifier ai
                LEFT JOIN airline_code ac
                    ON ai.id = ac.airline_id
                WHERE ai.id = ?
                GROUP BY ai.id
            SQL;

            $airlineRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($airlineId)
                ->getSingleRow();

            if ($airlineRow === null) {
                return null;
            }

            return new Airline($airlineRow["id"], $airlineRow["name"], explode(",", $airlineRow["codes"]), $airlineRow["logo"]);
        }

        public function selectAirlineByCode(string $airlineCode) : ?Airline {
            $sql = <<<'SQL'
                SELECT ai.id,
                    ai.name,
                    ai.logo,
                    COALESCE(GROUP_CONCAT(ac.code SEPARATOR ','), '') AS codes
                FROM airline_identifier ai
                LEFT JOIN airline_code ac
                    ON ai.id = ac.airline_id
                WHERE ai.id IN (
                    SELECT airline_id 
                    FROM airline_code 
                    WHERE code = ?
                )
                GROUP BY ai.id
            SQL;

            $airlineRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($airlineCode)
                ->getSingleRow();

            if ($airlineRow === null) {
                return null;
            }

            return new Airline($airlineRow["id"], $airlineRow["name"], explode(",", $airlineRow["codes"]), $airlineRow["logo"]);
        }

        public function selectLoggedFlightsWithoutEvent() : array {
            $sql = <<<SQL
                SELECT fl.flight,
                    fai.code AS `from`, 
                    tai.code AS `to`, 
                    fl.actual_departure AS start, 
                    fl.actual_arrival AS end, 
                    fl.registration, 
                    fl.aircraft, 
                    ai.id AS airline_id,
                    ai.name AS airline_name,
                    fl.from_airport_id, 
                    fl.to_airport_id, 
                    fl.actual_arrival - fl.scheduled_arrival AS delay,
                    fai.name AS from_airport_name,
                    fai.code AS from_airport_code, 
                    fai.latitude AS from_airport_latitude, 
                    fai.longitude AS from_airport_longitude, 
                    fai.country_category_id AS from_airport_country_category_id, 
                    fai.timezone AS from_airport_timezone, 
                    tai.name AS to_airport_name,
                    tai.code AS to_airport_code, 
                    tai.latitude AS to_airport_latitude, 
                    tai.longitude AS to_airport_longitude, 
                    tai.country_category_id AS to_airport_country_category_id, 
                    tai.timezone AS to_airport_timezone 
                FROM flight_log fl
                LEFT JOIN flight_event fe 
                    ON fe.flight = fl.flight 
                        AND fe.start = fl.scheduled_departure 
                INNER JOIN airport_identifier fai 
                    ON fl.from_airport_id = fai.id
                INNER JOIN airport_identifier tai 
                    ON fl.to_airport_id = tai.id 
                LEFT JOIN airline_code ac
                    ON fl.airline_code_id = ac.id
                LEFT JOIN airline_identifier ai
                    ON ac.airline_id = ai.id
                WHERE fe.id IS NULL
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getMappedResultSet(function($flightRow) {
                    $distance = $this->geocodingService->getDistance($flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"]);

                    $airlineIdentifier = $flightRow["airline_id"] === null || $flightRow["airline_name"] === null ? null : new AirlineIdentifier($flightRow["airline_id"], $flightRow["airline_name"]);
                    $from = new Airport($flightRow["from_airport_id"], $flightRow["from"], $flightRow["from_airport_name"], $flightRow["from_airport_code"], 
                        $flightRow["from_airport_country_category_id"] === null ? null : $this->categoryService->getCategoryIdentifierById($flightRow["from_airport_country_category_id"])->getName(), 
                        $flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["from_airport_timezone"]);
                    $to = new Airport($flightRow["to_airport_id"], $flightRow["to"], $flightRow["to_airport_name"], $flightRow["to_airport_code"],
                        $flightRow["to_airport_country_category_id"] === null ? null : $this->categoryService->getCategoryIdentifierById($flightRow["to_airport_country_category_id"])->getName(), 
                        $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"], $flightRow["to_airport_timezone"]);

                    return new Flight($flightRow["flight"], $flightRow["registration"], $flightRow["aircraft"], $airlineIdentifier, $distance, $from, $to, $flightRow["start"], $flightRow["end"], $flightRow["delay"]);
                }); 
        }

        public function selectLoggedFlightsForInterval(int $start, int $end, FlightSortingStrategy $flightSortingStrategy) : array {
            $sql = <<<SQL
                SELECT fl.flight,
                    fe.from, 
                    fe.to, 
                    fl.actual_departure AS start, 
                    fl.actual_arrival AS end, 
                    fl.registration, 
                    fl.aircraft, 
                    ai.id AS airline_id,
                    ai.name AS airline_name,
                    fl.from_airport_id, 
                    fl.to_airport_id, 
                    fl.actual_arrival - fl.scheduled_arrival AS delay,
                    fai.name AS from_airport_name,
                    fai.code AS from_airport_code, 
                    fai.latitude AS from_airport_latitude, 
                    fai.longitude AS from_airport_longitude, 
                    fai.country_category_id AS from_airport_country_category_id, 
                    fai.timezone AS from_airport_timezone, 
                    tai.name AS to_airport_name,
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
                LEFT JOIN airline_code ac
                    ON fl.airline_code_id = ac.id
                LEFT JOIN airline_identifier ai
                    ON ac.airline_id = ai.id
                WHERE fl.scheduled_departure >= ?
                    AND fl.scheduled_arrival <= ?
                {$flightSortingStrategy->getOrderByClause()}
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getMappedResultSet(function($flightRow) {
                    $distance = $this->geocodingService->getDistance($flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"]);

                    $airlineIdentifier = $flightRow["airline_id"] === null || $flightRow["airline_name"] === null ? null : new AirlineIdentifier($flightRow["airline_id"], $flightRow["airline_name"]);
                    $from = new Airport($flightRow["from_airport_id"], $flightRow["from"], $flightRow["from_airport_name"], $flightRow["from_airport_code"], 
                        $flightRow["from_airport_country_category_id"] === null ? null : $this->categoryService->getCategoryIdentifierById($flightRow["from_airport_country_category_id"])->getName(), 
                        $flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["from_airport_timezone"]);
                    $to = new Airport($flightRow["to_airport_id"], $flightRow["to"], $flightRow["to_airport_name"], $flightRow["to_airport_code"],
                        $flightRow["to_airport_country_category_id"] === null ? null : $this->categoryService->getCategoryIdentifierById($flightRow["to_airport_country_category_id"])->getName(), 
                        $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"], $flightRow["to_airport_timezone"]);

                    return new Flight($flightRow["flight"], $flightRow["registration"], $flightRow["aircraft"], $airlineIdentifier, $distance, $from, $to, $flightRow["start"], $flightRow["end"], $flightRow["delay"]);
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

            return $airportIdentifierRow === null ? null : new AirportIdentifier($airportIdentifierRow["id"], $airportIdentifierRow["name"],
                $airportIdentifierRow["code"], $this->categoryService->getCategoryIdentifierById($airportIdentifierRow["country_category_id"])->getName(),
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

        public function selectAllNonLoggedFlights() : array {
            $sql = <<<'SQL'
                SELECT fe.*
                FROM flight_event fe
                LEFT JOIN flight_log fl 
                    ON fe.flight = fl.flight 
                    AND fe.start = fl.scheduled_departure
                WHERE fl.actual_arrival IS NULL
                ORDER BY fe.end ASC
            SQL;       
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getMappedResultSet(function($flightRow) {
                    $from = new Airport(null, $flightRow["from"], null, null, null, null, null, null);
                    $to = new Airport(null, $flightRow["to"], null, null, null, null, null, null);
                    return new Flight($flightRow["flight"], null, null, null, null, $from, $to, $flightRow["start"], $flightRow["end"], null);
                });
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

        public function insertAirlineCodeId(string $code) : bool {    
            $sql = <<<'SQL'
                INSERT INTO airline_code (
                    code
                )
                VALUES (
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($code)
                ->execute() === 1;
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

        public function insertFlight(Flight $flight, $airlineCodeId, int $scheduledDeparture, int $scheduledArrival) : bool {
            $sql = <<<'SQL'
                INSERT INTO flight_log (
                    flight, 
                    registration,
                    airline_code_id,
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
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($flight->getFlight(), $flight->getRegistration(), $airlineCodeId, $flight->getAircraft(), $flight->getFrom()->getId(), 
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
                ->withParameters($eventId, $tripId, $flight->getFlight(), $flight->getFrom()->getShortName(),
                    $flight->getTo()->getShortName(), $flight->getStart(), $flight->getEnd())
                ->execute() === 1;
        }

        public function insertAirline(Airline $airline) : bool {
            $sql = <<<'SQL'
                INSERT INTO airline_identifier (
                    name,
                    logo
                )
                VALUES (
                    ?, 
                    ?
                )
            SQL;

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($airline->getName(), $airline->getLogo())
                ->execute() === 1;
                
            if ($wasInserted) {
                $airline->setId($this->databaseProvider->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function updateAirlineCodeAirline(string $airlineCodeId, ?string $airlineId) : bool {            
            $sql = <<<'SQL'
                UPDATE airline_code
                SET airline_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($airlineId, $airlineCodeId)
                ->execute() === 1;
        }

        public function updateAirlineName(string $airlineId, string $name) : bool {
            $sql = <<<'SQL'
                UPDATE airline_identifier
                SET name = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($name, $airlineId)
                ->execute() === 1;
        }

        public function updateAirlineLogo(string $airlineId, string $logo) : bool {
            $sql = <<<'SQL'
                UPDATE airline_identifier
                SET logo = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($logo, $airlineId)
                ->execute() === 1;
        }

        public function updateAirportName(string $airportId, string $name) : bool {
            $sql = <<<'SQL'
                UPDATE airport_identifier
                SET name = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($name, $airportId)
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

        public function deleteAirline(string $airlineId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM airline_identifier
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($airlineId)
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