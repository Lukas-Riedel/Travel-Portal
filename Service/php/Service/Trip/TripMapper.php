<?php
    namespace Service\Service\Trip;

    use Service\Service\Expense\ExpenseService;
    use Service\Service\Fitness\FitnessService;
    use Service\Service\Flight\FlightService;
    use Service\Service\Highlight\HighlightService;
    use Service\Service\Note\NoteService;
    use Service\Service\Place\PlaceService;
    use Service\Service\Statistics\StatisticsService;
    use Service\Service\Stay\StayService;

    class TripMapper {

        private const ONE_DAY_SECONDS = 86400;

        private readonly \DatabaseProvider $databaseProvider;

        private readonly \CalendarClient $calendarClient;

        private readonly PlaceService $placeService;
        private readonly StayService $stayService;
        private readonly FlightService $flightService;
        private readonly ExpenseService $expenseService;
        private readonly FitnessService $fitnessService;
        private readonly NoteService $noteService;
        private readonly HighlightService $highlightService;
        private readonly StatisticsService $statisticsService;

        public function __construct(\DatabaseProvider $databaseProvider, \CalendarClient $calendarClient,
            PlaceService $placeService, StayService $stayService, FlightService $flightService, ExpenseService $expenseService, FitnessService $fitnessService,
            NoteService $noteService, HighlightService $highlightService, StatisticsService $statisticsService) {
            $this->databaseProvider = $databaseProvider;
            $this->calendarClient = $calendarClient;
            $this->placeService = $placeService;
            $this->stayService = $stayService;
            $this->flightService = $flightService;
            $this->expenseService = $expenseService;
            $this->fitnessService = $fitnessService;
            $this->noteService = $noteService;
            $this->highlightService = $highlightService;
            $this->statisticsService = $statisticsService;
        }

        public function selectTripEventId(string $tripId) : ?string {
            $sql = <<<'SQL'
                SELECT id
                FROM trip_event
                WHERE trip_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getSingleColumn("id");
        }

        public function selectTripIdsContainingInterval(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT trip_id
                FROM (
                    SELECT trip_id, start, end
                    FROM trip_event
                    UNION
                    SELECT trip_id, start, end
                    FROM trip_day_trip
                ) t
                WHERE start <= ?
                    AND end >= ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdForEntity(int $entityStart, int $entityEnd) : ?string {
            $sql = <<<'SQL'
                SELECT trip_id
                FROM trip_event
                WHERE ? >= start
                    AND ? <= end
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters(($entityStart + $entityEnd) / 2, ($entityStart + $entityEnd) / 2)
                ->getSingleColumn("trip_id");
        }

        public function selectExistsDayTripsTrip(string $tripId) : bool {
            $sql = <<<'SQL'
                SELECT *
                FROM trip_day_trip
                WHERE trip_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getFirstRow() !== NULL;
        }

        public function selectTripIdentifier(string $name, ?int $year) : ?TripIdentifier {     
            $sql = <<<SQL
                SELECT * 
                FROM trip_identifier
                WHERE name = ?
                    AND year {$this->databaseProvider->getIsNullOrEqualTo($year)}
            SQL;

            $tripIdentifierRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($name)
                ->getFirstRow();

            if ($tripIdentifierRow === NULL) {
                return NULL;
            }

            return new TripIdentifier($tripIdentifierRow["id"], $tripIdentifierRow["name"], $tripIdentifierRow["year"],
                $this->highlightService->getHighlight($tripIdentifierRow["main_highlight_id"]));
        }

        public function selectTripIdentifierById(string $tripId) : ?TripIdentifier {     
            $sql = <<<'SQL'
                SELECT * 
                FROM trip_identifier
                WHERE id = ?
            SQL;

            $tripIdentifierRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getFirstRow();

            if ($tripIdentifierRow === NULL) {
                return NULL;
            }

            return new TripIdentifier($tripIdentifierRow["id"], $tripIdentifierRow["name"], $tripIdentifierRow["year"],
                $this->highlightService->getHighlight($tripIdentifierRow["main_highlight_id"]));
        }

        public function selectCandidateTrips(?string $tripId, array $includedEntities) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM trip_identifier
                WHERE :CONDITIONS
            SQL;
            
            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()->withClause("year IS NULL");
            if ($tripId !== NULL) {
                $whereClauseBuilder->withClause("id = ?", $tripId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($tripIdentifierRow) use(&$includedEntities) {
                    $countries = $this->placeService->getCountriesForCandidateTrip($tripIdentifierRow["id"]);
    
                    $notes = array();
                    if (in_array(TripIncludedEntity::Notes->value, $includedEntities)) {
                        $notes = $this->noteService->getTripNotes($tripIdentifierRow["id"]);                   
                    }
    
                    $publicHolidays = array();
                    if (in_array(TripIncludedEntity::PublicHolidays->value, $includedEntities)) {
                        $publicHolidays = $this->calendarClient->getPublicHolidaysForCountries($countries);
                    }
        
                    return new Trip($tripIdentifierRow["id"], $tripIdentifierRow["name"], NULL, NULL, 
                        NULL, NULL, $countries, array(), array(), array(), array(), array(), $notes, array(), array(), $publicHolidays);
                });
        }

        public function selectTripIdsForCreatedTripEvents(string $oldTripEventTableName) : array {
            $sql = <<<SQL
                SELECT nte.trip_id
                FROM trip_event nte
                LEFT JOIN {$oldTripEventTableName} ote
                    ON ote.id = nte.id
                WHERE ote.start IS NULL
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdsForUpdatedTripEvents(string $oldTripEventTableName) : array {
            $sql = <<<SQL
                SELECT nte.trip_id
                FROM trip_event nte
                INNER JOIN {$oldTripEventTableName} ote
                    ON ote.id = nte.id
                WHERE ote.start <> nte.start
                    OR ote.end <> nte.end
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdsForDeletedTripEvents(string $oldTripEventTableName) : array {
            $sql = <<<SQL
                SELECT ote.trip_id
                FROM {$oldTripEventTableName} ote
                LEFT JOIN trip_event nte
                    ON ote.id = nte.id
                WHERE nte.id IS NULL
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }
        
        public function selectRegularTrips(?string $tripId, ?int $year, ?int $start, ?int $end, array $includedEntities, TripSortingStrategy $tripSortingStrategy) : array {
            $sql = <<<SQL
                SELECT ti.*,
                    t.start,
                    t.end
                FROM (
                    SELECT trip_id, start, end
                    FROM trip_event
                    UNION
                    SELECT trip_id, start, end
                    FROM trip_day_trip
                ) t
                INNER JOIN trip_identifier ti
                    ON t.trip_id = ti.id
                WHERE :CONDITIONS
                {$tripSortingStrategy->value}
            SQL;
            
            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder();
            if ($year !== NULL) {
                $whereClauseBuilder->withClause("ti.year = ?", $year);
            }
            if ($tripId !== NULL) {
                $whereClauseBuilder->withClause("ti.id = ?", $tripId);
            }
            if ($start !== NULL) {
                $whereClauseBuilder->withClause("t.start >= ?", $start);
            }
            if ($end !== NULL) {
                $whereClauseBuilder->withClause("t.end <= ?", $end);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($tripRow) use(&$includedEntities) {
                    $countries = $this->placeService->getCountriesForTrip($tripRow["id"]);
                    
                    $expenses = array();
                    if (in_array(TripIncludedEntity::Expenses->value, $includedEntities)) {
                        $expenses = $this->expenseService->getExpensesForTrip($tripRow["id"]);            
                    }
    
                    $stays = array();
                    if (in_array(TripIncludedEntity::Stays->value, $includedEntities)) {
                        $stays = $this->stayService->getStaysForTrip($tripRow["id"]);                        
                    }
    
                    $flights = array();
                    if (in_array(TripIncludedEntity::Flights->value, $includedEntities)) {
                        $flights = $this->flightService->getScheduledFlightsForTrip($tripRow["id"]);             
                    }
    
                    $watchedFlights = array();
                    if (in_array(TripIncludedEntity::WatchedFlights->value, $includedEntities)) {
                        $watchedFlights = $this->flightService->getWatchedFlightsForTrip($tripRow["id"]);
                    }
    
                    $fitness = array();
                    if (in_array(TripIncludedEntity::Fitness->value, $includedEntities)) {
                        $startOfDay = $tripRow["start"] - ($tripRow["start"] % self::ONE_DAY_SECONDS);
                        while ($startOfDay < $tripRow["end"]) {
                            $fitness[] = $this->fitnessService->getFitnessRecordForOneDay($startOfDay);
                            $startOfDay += self::ONE_DAY_SECONDS;
                        }
                    }
    
                    $notes = array();
                    if (in_array(TripIncludedEntity::Notes->value, $includedEntities)) {
                        $notes = $this->noteService->getTripNotes($tripRow["id"]);                   
                    }
    
                    $highlights = array();
                    if (in_array(TripIncludedEntity::Highlights->value, $includedEntities)) {
                        $highlights = $this->highlightService->getTripHighlights($tripRow["id"]);        
                    }
    
                    $statistics = array();
                    if (in_array(TripIncludedEntity::Statistics->value, $includedEntities)) {
                        $statistics = $this->statisticsService->getTripStatistics($tripRow["id"]);                 
                    }
    
                    $publicHolidays = array();
                    if (in_array(TripIncludedEntity::PublicHolidays->value, $includedEntities)) {
                        $publicHolidays = $this->calendarClient->getPublicHolidaysForDatesInCountries(function($country) use(&$tripRow) {
                            return $this->placeService->getDatesForTripAndCountry($tripRow["id"], $country);
                        }, $countries);                               
                    }
    
                    return new Trip($tripRow["id"], $tripRow["name"], $tripRow["year"], $this->highlightService->getHighlight($tripRow["main_highlight_id"]), $tripRow["start"],
                        $tripRow["end"], $countries, $expenses, $stays, $flights, $watchedFlights, $fitness, $notes, $highlights, $statistics, $publicHolidays);
                });
        }

        public function insertDayTripsTrip(Trip $trip) : bool {
            $sql = <<<'SQL'
                INSERT INTO trip_day_trip (
                    trip_id, 
                    start, 
                    end
                )
                VALUES (
                    ?, 
                    ?, 
                    ?
                )
            SQL;            

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($trip->getId(), $trip->getStart(), $trip->getEnd())
                ->execute() === 1;
        }

        public function insertTripIdentifier(TripIdentifier $tripIdentifier) : bool {
            $sql = <<<'SQL'
                INSERT INTO trip_identifier (
                    name, 
                    year
                )
                VALUES (
                    ?, 
                    ?
                )
            SQL;            

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripIdentifier->getName(), $tripIdentifier->getYear())
                ->execute() === 1;

            if ($wasInserted) {
                $tripIdentifier->setId($this->databaseProvider->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function insertTripEvent(Trip $trip, string $eventId) : bool {
            $sql = <<<'SQL'
                INSERT INTO trip_event (
                    id,
                    trip_id, 
                    start, 
                    end
                )
                VALUES (
                    ?,
                    ?, 
                    ?, 
                    ?
                )
            SQL;            

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($eventId, $trip->getId(), $trip->getStart(), $trip->getEnd())
                ->execute() === 1;
        }

        public function updateDayTripsTripDates(string $tripId, int $start, int $end) : bool {
            $sql = <<<'SQL'
                UPDATE trip_day_trip
                SET start = ?,
                    end = ?
                WHERE trip_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end, $tripId)
                ->execute() === 1;
        }

        public function updateTripMainHighlight(string $tripId, string $highlightIdentifier) : bool {
            $sql = <<<'SQL'
                UPDATE trip_identifier
                SET main_highlight_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($highlightIdentifier, $tripId)
                ->execute() === 1;
        }

        public function updateTripName(string $tripId, string $name) : bool {
            $sql = <<<'SQL'
                UPDATE trip_identifier
                SET name = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($name, $tripId)
                ->execute() === 1;
        }

        public function deleteAllTripEvents() : int {
            $sql = <<<'SQL'
                DELETE
                FROM trip_event
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }

        public function deleteAllDayTripsTrips() : int {
            $sql = <<<'SQL'
                DELETE
                FROM trip_day_trip
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }

        public function createTripEventTemporaryTable(string $tableName) : void {            
            $sql = <<<SQL
                DROP TEMPORARY TABLE IF EXISTS {$tableName}
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();    

            $sql = <<<SQL
                CREATE TEMPORARY TABLE {$tableName} AS
                    SELECT *
                    FROM trip_event
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }
    }
?>