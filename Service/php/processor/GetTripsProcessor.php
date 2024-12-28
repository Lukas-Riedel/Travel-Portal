<?php
    require_once(dirname(__FILE__) . "/../model/Trip.php");
    require_once(dirname(__FILE__) . "/../model/Expense.php");
    require_once(dirname(__FILE__) . "/../model/Note.php");
    require_once(dirname(__FILE__) . "/../model/Airport.php");
    require_once(dirname(__FILE__) . "/../model/Stay.php");
    require_once(dirname(__FILE__) . "/../model/Flight.php");
    require_once(dirname(__FILE__) . "/../model/PublicHoliday.php");
    require_once(dirname(__FILE__) . "/../model/Fitness.php");
    require_once(dirname(__FILE__) . "/GetPublicHolidaysProcessor.php");

    class GetTripsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $statisticsService;
            
            $result = array();

            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if (isset($input["year"])) {
                $whereClauseBuilder->withClause("year = ?", $input["year"]);
            }
            if (isset($input["tripId"])) {
                $whereClauseBuilder->withClause("trip_id = ?", $input["tripId"]);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $tripRows = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_summary {{WHERE CLAUSE}}", $whereClause)
                ->getResultSet();

            foreach ($tripRows as &$tripRow) {
                $countries = $databaseProvider
                    ->statementBuilder("SELECT DISTINCT country FROM place_summary WHERE trip_id = ? AND layover = 0 GROUP BY country ORDER BY MIN(start)")
                    ->withParameters($tripRow["trip_id"])
                    ->getResultSetForColumn("country");

                $expenses = array();
                $stays = array();
                $flights = array();
                $watchedFlights = array();
                $layovers = array();
                $expenses = array();
                $fitness = array();
                $notes = array();
                $highlights = array();
                $stats = array();
                $publicHolidays = array();
                
                $includeExpenses = isset($input["includeExpenses"]) && $input["includeExpenses"] == "true";
                if ($includeExpenses || isset($input["tripId"])) {
                    $expenses = $this->getExpenses($tripRow);                
                }

                $includeStays = isset($input["includeStays"]) && $input["includeStays"] == "true";
                if ($includeStays || isset($input["tripId"])) {
                    $stays = $this->getStays($tripRow);                    
                }

                $includeFlights = isset($input["includeFlights"]) && $input["includeFlights"] == "true";
                if ($includeFlights || isset($input["tripId"])) {
                    $flights = $this->getFlights("flight_event", $tripRow);                    
                }

                $includeWatchedFlights = isset($input["includeWatchedFlights"]) && $input["includeWatchedFlights"] == "true";
                if ($includeWatchedFlights || isset($input["tripId"])) {
                    $watchedFlights = $this->getFlights("flight_watched_event", $tripRow);
                }

                $includeLayovers = isset($input["includeLayovers"]) && $input["includeLayovers"] == "true";
                if ($includeLayovers || isset($input["tripId"])) {
                    $layovers = $this->getLayovers($tripRow);                    
                }

                $includeFitness = isset($input["includeFitness"]) && $input["includeFitness"] == "true";
                if ($includeFitness || isset($input["tripId"])) {
                    $fitness = $this->getFitness($tripRow);                    
                }

                $includeNotes = isset($input["includeNotes"]) && $input["includeNotes"] == "true";
                if ($includeNotes || isset($input["tripId"])) {
                    $notes = $this->getNotes($tripRow);                      
                }

                $includeHighlights = isset($input["includeHighlights"]) && $input["includeHighlights"] == "true";
                if ($includeHighlights || isset($input["tripId"])) {
                    $highlights = $this->getHighlights($tripRow);                      
                }

                $includeStats = isset($input["includeStats"]) && $input["includeStats"] == "true";
                if ($includeStats || isset($input["tripId"])) {
                    $stats = $statisticsService->getTripStatistics($tripRow["trip_id"]);                 
                }

                $includePublicHolidays = isset($input["includePublicHolidays"]) && $input["includePublicHolidays"] == "true";
                if ($includePublicHolidays || isset($input["tripId"])) {
                    $publicHolidays = $this->getPublicHolidays($tripRow);                               
                }

                $result[] = new Trip($tripRow["trip_id"], $tripRow["name"], $tripRow["year"], $this->getHighlight($tripRow["main_highlight_id"]), $tripRow["start"], $tripRow["end"], $countries,
                    $tripRow["cost"], $tripRow["days"], isset($tripRow["working_days"]) ? $tripRow["working_days"] : NULL, isset($tripRow["expected_vacation"]) ? $tripRow["expected_vacation"] : NULL,
                    isset($tripRow["max_vacation"]) ? $tripRow["max_vacation"] : NULL, $expenses, $stays, $flights, $watchedFlights, $layovers, $fitness, $notes, $highlights, $stats, $publicHolidays);
            }

            return $result;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }

        private function getNotes($tripRow) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT id, content FROM note WHERE trip_id = ?")
                ->withParameters($tripRow["trip_id"])
                ->getMappedResultSet(function ($noteRow) {
                    return new Note($noteRow["id"], $noteRow["content"]);
                });
        }
    
        private function getStays($tripRow) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT * FROM stay_event WHERE trip_id = ? ORDER BY start")
                ->withParameters($tripRow["trip_id"])
                ->getMappedResultSet(function ($stayRow) {
                    return new Stay($stayRow["name"], $stayRow["address"], $stayRow["start"], $stayRow["end"]);
                });
        }
    
        private function getFlights($table, $tripRow) {
            global $databaseProvider, $geocodingClient;

            $flightRows = $databaseProvider
                ->statementBuilder("SELECT fe.flight, fe.from, fe.to, COALESCE(fl.actual_departure, fe.start) AS start, COALESCE(fl.actual_arrival, fe.end) AS end, fl.registration, fl.aircraft, fl.from_airport_id, fl.to_airport_id, fai.code AS from_airport_code, fai.latitude AS from_airport_latitude, fai.longitude AS from_airport_longitude, fai.country AS from_airport_country, fai.timezone AS from_airport_timezone, tai.code AS to_airport_code, tai.latitude AS to_airport_latitude, tai.longitude AS to_airport_longitude, tai.country AS to_airport_country, tai.timezone AS to_airport_timezone FROM " . $table . " fe LEFT JOIN flight_log fl ON fe.flight = fl.flight AND fe.start = fl.scheduled_departure LEFT JOIN airport_identifier fai ON fl.from_airport_id = fai.id LEFT JOIN airport_identifier tai ON fl.to_airport_id = tai.id  WHERE fe.trip_id = ? ORDER BY start")
                ->withParameters($tripRow["trip_id"])
                ->getResultSet();

            $result = array();
            
            foreach ($flightRows as &$flightRow) {
                $distance = NULL;
                if ($flightRow["from_airport_latitude"] != NULL && $flightRow["from_airport_longitude"] != NULL && $flightRow["to_airport_latitude"] != NULL && $flightRow["to_airport_longitude"] != NULL) {
                    $distance = $geocodingClient->getDistance($flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"]);
                }
                $from = new Airport($flightRow["from_airport_id"], $flightRow["from"], $flightRow["from_airport_code"], $flightRow["from_airport_country"], 
                    $flightRow["from_airport_latitude"], $flightRow["from_airport_longitude"], $flightRow["from_airport_timezone"]);
                $to = new Airport($flightRow["to_airport_id"], $flightRow["to"], $flightRow["to_airport_code"], $flightRow["to_airport_country"], 
                    $flightRow["to_airport_latitude"], $flightRow["to_airport_longitude"], $flightRow["to_airport_timezone"]);

                $result[] = new Flight($flightRow["flight"], $flightRow["registration"], $flightRow["aircraft"], $distance, $from, $to, $flightRow["start"], $flightRow["end"]);
            }
    
            return $result;
        }
    
        private function getLayovers($tripRow) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT place_id FROM place_summary WHERE trip_id = ? AND layover = 1")
                ->withParameters($tripRow["trip_id"])
                ->getResultSetForColumn("place_id");
        }
    
        private function getPublicHolidays($tripRow) {
            global $databaseProvider;

            $result = array();

            $visitedCountryRows = $databaseProvider
                ->statementBuilder("SELECT country, GROUP_CONCAT(DISTINCT DATE_FORMAT(FROM_UNIXTIME(start),'%e.%c.%Y') SEPARATOR ',') AS dates FROM place_summary WHERE trip_id = ? GROUP BY country")
                ->withParameters($tripRow["trip_id"])
                ->getResultSet();

            $getPublicHolidaysProcessor = new GetPublicHolidaysProcessor();

            foreach ($visitedCountryRows as $visitedCountryRow) {
                $holidays = $getPublicHolidaysProcessor
                    ->process(array(
                        "country" => $visitedCountryRow["country"]));

                $holidaysMap = array();
                foreach ($holidays as &$holiday) {
                    $holidaysMap[$holiday->getDate()] = $holiday;
                }

                foreach (explode(",", $visitedCountryRow["dates"]) as &$date) {
                    if (array_key_exists($date, $holidaysMap)) {
                        $result[] = new PublicHoliday($holidaysMap[$date]->getName(), $visitedCountryRow["country"], $date);
                    }
                }
            }

            return $result;
        }
        
        private function getExpenses($tripRow) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT * FROM expense_summary WHERE trip_id = ?")
                ->withParameters($tripRow["trip_id"])
                ->getMappedResultSet(function ($expenseRow) {
                    return new Expense($expenseRow["id"], $expenseRow["description"], $expenseRow["value"], $expenseRow["currency"], $expenseRow["main_currency_value"], $expenseRow["type"]);
                });
        }
    
        private function getFitness($tripRow) {
            global $databaseProvider, $configuration;

            $result = array();

            $rangeRows = $databaseProvider
                ->statementBuilder("SELECT DISTINCT start - (start % 86400) AS start, start - (start % 86400) + 86400 AS end FROM place_summary WHERE trip_id = ? AND start - (start % 86400) < UNIX_TIMESTAMP() ORDER BY start")
                ->withParameters($tripRow["trip_id"])
                ->getResultSet();
            
            foreach ($rangeRows as &$rangeRow) {
                $fitnessRow = $databaseProvider
                    ->statementBuilder("SELECT SUM(steps) AS steps, SUM(minutes) AS minutes, SUM(calories) AS calories, SUM(distance) AS distance FROM fitness WHERE timestamp >= ? AND timestamp < ?")
                    ->withParameters($rangeRow["start"], $rangeRow["end"])
                    ->getSingleRow();

                $result[] = new Fitness(intval($fitnessRow["steps"]), intval($fitnessRow["minutes"]), intval($fitnessRow["calories"]), doubleval($fitnessRow["distance"]));
            }
    
            return $result;
        }

        private function getHighlights($tripRow) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_trip ht INNER JOIN highlight_identifier hi ON ht.highlight_id = hi.id LEFT JOIN photo p ON hi.photo_id = p.id WHERE ht.id = ?")
                ->withParameters($tripRow["trip_id"])
                ->getMappedResultSet(function ($highlightRow) { 
                    return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["focal_length"], 
                        $highlightRow["aperture"], $highlightRow["shutter_speed"], $highlightRow["iso"], $highlightRow["timestamp"]);
                });
        }

        private function getHighlight($highlightId) {
            global $databaseProvider;            
                
            $mainHighlightIdentifierRow = $databaseProvider
            ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.id = ?")
            ->withParameters($highlightId)
            ->getSingleRow();
            
           return $mainHighlightIdentifierRow == NULL ? NULL : new Highlight($mainHighlightIdentifierRow["id"], $mainHighlightIdentifierRow["thumbnail_url"], $mainHighlightIdentifierRow["full_url"], 
                $mainHighlightIdentifierRow["focal_length"], $mainHighlightIdentifierRow["aperture"], $mainHighlightIdentifierRow["shutter_speed"], $mainHighlightIdentifierRow["iso"], $mainHighlightIdentifierRow["timestamp"]);
        }
    }
?>