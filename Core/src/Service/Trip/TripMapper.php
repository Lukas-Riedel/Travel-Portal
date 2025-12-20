<?php
    namespace Core\Service\Trip;

    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Expense\ExpenseService;
    use Core\Service\Fitness\FitnessService;
    use Core\Service\Flight\FlightService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Note\NoteService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Statistics\StatisticsService;
    use Core\Service\Stay\StayService;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Calendar\CalendarClient;

    class TripMapper {

        private readonly DatabaseClient $databaseClient;

        private readonly CalendarClient $calendarClient;

        private readonly PlaceService $placeService;
        private readonly StayService $stayService;
        private readonly FlightService $flightService;
        private readonly ExpenseService $expenseService;
        private readonly FitnessService $fitnessService;
        private readonly NoteService $noteService;
        private readonly HighlightService $highlightService;
        private readonly StatisticsService $statisticsService;

        public function __construct(DatabaseClient $databaseClient, CalendarClient $calendarClient,
            PlaceService $placeService, StayService $stayService, FlightService $flightService, ExpenseService $expenseService, FitnessService $fitnessService,
            NoteService $noteService, HighlightService $highlightService, StatisticsService $statisticsService) {
            $this->databaseClient = $databaseClient;
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getSingleColumn("id");
        }

        public function selectTripIdsContainingInterval(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT trip_id
                FROM trip_event
                WHERE "start" <= ?
                    AND "end" >= ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdForEntity(int $entityStart, int $entityEnd) : ?string {
            $sql = <<<'SQL'
                SELECT trip_id
                FROM trip_event
                WHERE ? >= "start"
                    AND ? <= "end"
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters(($entityStart + $entityEnd) / 2, ($entityStart + $entityEnd) / 2)
                ->getSingleColumn("trip_id");
        }

        public function selectTripIdentifier(string $name, ?int $year) : ?TripIdentifier {     
            $sql = <<<SQL
                SELECT * 
                FROM trip_identifier
                WHERE name = ?
                    AND year {$this->databaseClient->getIsNullOrEqualTo($year)}
            SQL;

            $tripIdentifierRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($name)
                ->getFirstRow();

            if ($tripIdentifierRow === null) {
                return null;
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

            $tripIdentifierRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getFirstRow();

            if ($tripIdentifierRow === null) {
                return null;
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
            
            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder()->withClause("year IS NULL");
            if ($tripId !== null) {
                $whereClauseBuilder->withClause("id = ?", $tripId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($tripIdentifierRow) use(&$includedEntities) {
                    $countryCategories = $this->placeService->getCountryCategoriesForCandidateTrip($tripIdentifierRow["id"]);
    
                    $notes = array();
                    if (in_array(TripIncludedEntity::Notes->value, $includedEntities)) {
                        $notes = $this->noteService->getTripNotes($tripIdentifierRow["id"]);                   
                    }
    
                    $publicHolidays = array();
                    if (in_array(TripIncludedEntity::PublicHolidays->value, $includedEntities)) {
                        $publicHolidays = $this->calendarClient->getPublicHolidaysForCategories($countryCategories);
                    }
        
                    return new Trip($tripIdentifierRow["id"], $tripIdentifierRow["name"], null, null, 
                        null, null, array_map(fn($countryCategory) => $countryCategory->getName(), $countryCategories),
                        array(), array(), array(), array(), array(), $notes, array(), array(), $publicHolidays);
                });
        }

        public function selectTripIdsForCreatedTripEvents(string $oldTripEventTableName) : array {
            $sql = <<<SQL
                SELECT nte.trip_id
                FROM trip_event nte
                LEFT JOIN {$oldTripEventTableName} ote
                    ON ote.id = nte.id
                WHERE ote."start" IS NULL
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }

        public function selectTripIdsForUpdatedTripEvents(string $oldTripEventTableName) : array {
            $sql = <<<SQL
                SELECT nte.trip_id
                FROM trip_event nte
                INNER JOIN {$oldTripEventTableName} ote
                    ON ote.id = nte.id
                WHERE ote."start" <> nte."start"
                    OR ote."end" <> nte."end"
            SQL;

            return $this->databaseClient
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getResultSetForColumn("trip_id");
        }
        
        public function selectRegularTrips(?string $tripId, ?int $year, ?int $start, ?int $end, array $includedEntities, TripSortingStrategy $tripSortingStrategy) : array {
            $sql = <<<SQL
                SELECT ti.*,
                    te."start",
                    te."end"
                FROM trip_event te
                INNER JOIN trip_identifier ti
                    ON te.trip_id = ti.id
                WHERE :CONDITIONS
                {$tripSortingStrategy->getOrderByClause()}
            SQL;
            
            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder();
            if ($year !== null) {
                $whereClauseBuilder->withClause("ti.year = ?", $year);
            }
            if ($tripId !== null) {
                $whereClauseBuilder->withClause("ti.id = ?", $tripId);
            }
            if ($start !== null) {
                $whereClauseBuilder->withClause("te.\"start\" >= ?", $start);
            }
            if ($end !== null) {
                $whereClauseBuilder->withClause("te.\"end\" <= ?", $end);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($tripRow) use(&$includedEntities) {
                    $countryCategories = $this->placeService->getCountryCategoriesForTrip($tripRow["id"]);
                    
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
                        $startOfDay = $tripRow["start"] - ($tripRow["start"] % CommonConstants::ONE_DAY_SECONDS);
                        while ($startOfDay < $tripRow["end"]) {
                            $dayStart = max($startOfDay, $tripRow["start"]);
                            $dayEnd = min($startOfDay + CommonConstants::ONE_DAY_SECONDS, $tripRow["end"]);

                            $fitness[] = $this->fitnessService->getFitnessRecordForInterval($dayStart, $dayEnd);
                            $startOfDay += CommonConstants::ONE_DAY_SECONDS;
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
                    if (in_array(TripIncludedEntity::Statistics->value, $includedEntities) && $tripRow["start"] < time()) {
                        $statistics = $this->statisticsService->getTripStatistics($tripRow["id"]);                 
                    }
    
                    $publicHolidays = array();
                    if (in_array(TripIncludedEntity::PublicHolidays->value, $includedEntities)) {
                        $publicHolidays = $this->calendarClient->getPublicHolidaysForDatesInCategories(function($countryCategory) use(&$tripRow) {
                            return $this->placeService->getDatesForTripAndCountry($tripRow["id"], $countryCategory->getId());
                        }, $countryCategories);                               
                    }
    
                    return new Trip($tripRow["id"], $tripRow["name"], $tripRow["year"], $this->highlightService->getHighlight($tripRow["main_highlight_id"]), $tripRow["start"],
                        $tripRow["end"], array_map(fn($countryCategory) => $countryCategory->getName(), $countryCategories), $expenses, $stays, $flights, $watchedFlights,
                        $fitness, $notes, $highlights, $statistics, $publicHolidays);
                });
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
                RETURNING id
            SQL;            

            $id = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripIdentifier->getName(), $tripIdentifier->getYear())
                ->getSingleColumn("id");

            if ($id === null) {
                return false;
            }

            $tripIdentifier->setId($id);
            return true;
        }

        public function insertTripEvent(Trip $trip, string $eventId) : bool {
            $sql = <<<'SQL'
                INSERT INTO trip_event (
                    id,
                    trip_id, 
                    "start", 
                    "end"
                )
                VALUES (
                    ?,
                    ?, 
                    ?, 
                    ?
                )
            SQL;            

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($eventId, $trip->getId(), $trip->getStart(), $trip->getEnd())
                ->execute() === 1;
        }
        
        public function insertCandidateTrip(string $tripId) : bool {
            $sql = <<<'SQL'
                INSERT INTO trip_candidate (
                    trip_id
                )
                VALUES (
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->execute();
        }

        public function updateTripMainHighlight(string $tripId, string $highlightIdentifier) : bool {
            $sql = <<<'SQL'
                UPDATE trip_identifier
                SET main_highlight_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($name, $tripId)
                ->execute() === 1;
        }

        public function deleteAllTripEvents() : int {
            $sql = <<<'SQL'
                DELETE
                FROM trip_event
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }

        public function deleteStaleTripIdentifiers() : int {
            $sql = <<<'SQL'
                DELETE 
                FROM trip_identifier ti
                WHERE NOT EXISTS (
                        SELECT 1 
                        FROM trip_event te
                        WHERE te.trip_id = ti.id
                    ) AND NOT EXISTS(
                        SELECT 1 
                        FROM trip_candidate tc
                        WHERE tc.trip_id = ti.id
                    )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }
        
        public function deleteCandidateTrip(string $tripId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM trip_candidate
                WHERE trip_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->execute();
        }

        public function createTripEventTemporaryTable(string $tableName) : void {            
            $sql = <<<SQL
                DROP TABLE IF EXISTS {$tableName}
            SQL;
            
            $this->databaseClient
                ->statementBuilder($sql)
                ->execute();    

            $sql = <<<SQL
                CREATE TEMPORARY TABLE {$tableName} AS
                    SELECT *
                    FROM trip_event
            SQL;
            
            $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }
    }
?>