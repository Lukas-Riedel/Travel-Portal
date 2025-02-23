<?php
    class FlightMapper {

        private readonly DatabaseProvider $databaseProvider;

        private readonly CategoryService $categoryService;

        private readonly GeocodingService $geocodingService;

        public function __construct(DatabaseProvider $databaseProvider, CategoryService $categoryService, GeocodingService $geocodingService) {
            $this->databaseProvider = $databaseProvider;
            $this->categoryService = $categoryService;
            $this->geocodingService = $geocodingService;
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
                $this->categoryService->getCategoryIdentifier($airportIdentifierRow["country_category_id"])->getName(),
                $airportIdentifierRow["latitude"], $airportIdentifierRow["longitude"], $airportIdentifierRow["timezone"]);
        }

        public function selectAllLoggedFlights() : array {
            $sql = <<<'SQL'
                SELECT f.*,
                    l.*,
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
                FROM flight_log l
                LEFT JOIN airport_identifier fai
                    ON l.from_airport_id = fai.id
                LEFT JOIN airport_identifier tai
                    ON l.to_airport_id = tai.id
                LEFT JOIN flight_event f 
                    ON l.scheduled_departure = f.start
                ORDER BY actual_departure DESC
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getMappedResultSet(function($loggedFlightRow) {                    
                    $distance = $this->geocodingService->getDistance($loggedFlightRow["from_airport_latitude"], $loggedFlightRow["from_airport_longitude"],
                        $loggedFlightRow["to_airport_latitude"], $loggedFlightRow["to_airport_longitude"]);
                    
                    $from = new Airport($loggedFlightRow["from_airport_id"], $loggedFlightRow["from"], $loggedFlightRow["from_airport_code"], $this->categoryService->getCategoryIdentifier($loggedFlightRow["from_airport_country_category_id"])->getName(), 
                        $loggedFlightRow["from_airport_latitude"], $loggedFlightRow["from_airport_longitude"], $loggedFlightRow["from_airport_timezone"]);
                    $to = new Airport($loggedFlightRow["to_airport_id"], $loggedFlightRow["to"], $loggedFlightRow["to_airport_code"], $this->categoryService->getCategoryIdentifier($loggedFlightRow["to_airport_country_cateogry_id"])->getName(), 
                        $loggedFlightRow["to_airport_latitude"], $loggedFlightRow["to_airport_longitude"], $loggedFlightRow["to_airport_timezone"]);

                    return new Flight($loggedFlightRow["flight"], $loggedFlightRow["registration"], $loggedFlightRow["aircraft"], $distance, $from, $to, $loggedFlightRow["actual_departure"], $loggedFlightRow["actual_arrival"]);
                });
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
                ->execute();
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
    }
?>