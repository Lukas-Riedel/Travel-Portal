<?php
    class TripMapper {

        private readonly DatabaseProvider $databaseProvider;

        private readonly CalendarClient $calendarClient;

        private readonly PlaceService $placeService;
        private readonly StayService $stayService;
        private readonly FlightService $flightService;
        private readonly ExpenseService $expenseService;
        private readonly FitnessService $fitnessService;
        private readonly NoteService $noteService;
        private readonly HighlightService $highlightService;
        private readonly StatisticsService $statisticsService;

        public function __construct(DatabaseProvider $databaseProvider, CalendarClient $calendarClient, PlaceService $placeService, StayService $stayService,
            FlightService $flightService, ExpenseService $expenseService, FitnessService $fitnessService, NoteService $noteService,
            HighlightService $highlightService, StatisticsService $statisticsService) {
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
                FROM trip_summary
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
                        $notes = $this->noteService->getNotesForTrip($tripIdentifierRow["id"]);                   
                    }
    
                    $publicHolidays = array();
                    if (in_array(TripIncludedEntity::PublicHolidays->value, $includedEntities)) {
                        $publicHolidays = $this->calendarClient->getPublicHolidaysForCountries($countries);
                    }
        
                    return new Trip($tripIdentifierRow["id"], $tripIdentifierRow["name"], NULL, NULL, 
                        NULL, NULL, $countries, NULL, $this->placeService->getDaysForCandidateTrip($tripIdentifierRow["id"]), NULL, NULL, NULL, array(), array(),
                        array(), array(), array(), array(), $notes, array(), array(), $publicHolidays);
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
        
        public function selectRegularTrips(?string $tripId, ?int $year, array $includedEntities) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM trip_summary
                WHERE :CONDITIONS
            SQL;
            
            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder();
            if ($year !== NULL) {
                $whereClauseBuilder->withClause("year = ?", $year);
            }
            if ($tripId !== NULL) {
                $whereClauseBuilder->withClause("trip_id = ?", $tripId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($tripRow) use(&$includedEntities) {
                    $countries = $this->placeService->getCountriesForTrip($tripRow["trip_id"]);
                    
                    $expenses = array();
                    if (in_array(TripIncludedEntity::Expenses->value, $includedEntities)) {
                        $expenses = $this->expenseService->getExpensesForTrip($tripRow["trip_id"]);            
                    }
    
                    $stays = array();
                    if (in_array(TripIncludedEntity::Stays->value, $includedEntities)) {
                        $stays = $this->stayService->getStaysForTrip($tripRow["trip_id"]);                        
                    }
    
                    $flights = array();
                    if (in_array(TripIncludedEntity::Flights->value, $includedEntities)) {
                        $flights = $this->flightService->getFlightsForTrip($tripRow["trip_id"]);             
                    }
    
                    $watchedFlights = array();
                    if (in_array(TripIncludedEntity::WatchedFlights->value, $includedEntities)) {
                        $watchedFlights = $this->flightService->getWatchedFlightsForTrip($tripRow["trip_id"]);
                    }
    
                    $layovers = array();
                    if (in_array(TripIncludedEntity::Layovers->value, $includedEntities)) {
                        $layovers = $this->placeService->getLayoversForTrip($tripRow["trip_id"]);                   
                    }
    
                    $fitness = array();
                    if (in_array(TripIncludedEntity::Fitness->value, $includedEntities)) {
                        $startOfDays = array();
    
                        // TODO: Include fitness records for all days, not only those with a visited place. Some frontend adjustments are needed.
                        $tripPlaces = $this->placeService->getRegularPlaces(NULL, NULL, $tripRow["trip_id"], NULL, NULL, NULL, NULL, array());
                        foreach ($tripPlaces as &$tripPlace) {
                            foreach ($tripPlace->getDates() as &$date) {
                                // TODO: Calculate start of days based on the timezone of the client (i.e., an extra GET parameter with timezone).
                                $startOfDay = $date->getStart() - ($date->getStart() % 86400);
                                if (!in_array($startOfDay, $startOfDays)) {
                                    $startOfDays[] = $startOfDay;
                                }
                            }
                        }
                        sort($startOfDays);
    
                        foreach ($startOfDays as &$startOfDay) {
                            $fitness[] = $this->fitnessService->getFitnessRecordForDay($startOfDay);
                        }
                    }
    
                    $notes = array();
                    if (in_array(TripIncludedEntity::Notes->value, $includedEntities)) {
                        $notes = $this->noteService->getNotesForTrip($tripRow["trip_id"]);                   
                    }
    
                    $highlights = array();
                    if (in_array(TripIncludedEntity::Highlights->value, $includedEntities)) {
                        $highlights = $this->highlightService->getTripHighlights($tripRow["trip_id"]);        
                    }
    
                    $statistics = array();
                    if (in_array(TripIncludedEntity::Statistics->value, $includedEntities)) {
                        $statistics = $this->statisticsService->getTripStatistics($tripRow["trip_id"]);                 
                    }
    
                    $publicHolidays = array();
                    if (in_array(TripIncludedEntity::PublicHolidays->value, $includedEntities)) {
                        $publicHolidays = $this->calendarClient->getPublicHolidaysForDatesInCountries(function($country) use(&$tripRow) {
                            return $this->placeService->getDatesForTripAndCountry($tripRow["trip_id"], $country);
                        }, $countries);                               
                    }
    
                    return new Trip($tripRow["trip_id"], $tripRow["name"], $tripRow["year"], $this->highlightService->getHighlight($tripRow["main_highlight_id"]), $tripRow["start"], $tripRow["end"], $countries,
                        $tripRow["cost"], $tripRow["days"], isset($tripRow["working_days"]) ? $tripRow["working_days"] : NULL, isset($tripRow["expected_vacation"]) ? $tripRow["expected_vacation"] : NULL,
                        isset($tripRow["max_vacation"]) ? $tripRow["max_vacation"] : NULL, $expenses, $stays, $flights, $watchedFlights, $layovers, $fitness, $notes, $highlights, $statistics, $publicHolidays);
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

        public function deleteCandidateTrip(string $tripId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM place_candidate_event
                WHERE trip_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->execute();
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