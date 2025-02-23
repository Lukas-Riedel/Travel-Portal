<?php
    require_once(dirname(__FILE__) . "/../model/TripIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Trip.php");
    require_once(dirname(__FILE__) . "/../model/Highlight.php");
    require_once(dirname(__FILE__) . "/../model/Expense.php");
    require_once(dirname(__FILE__) . "/../model/Note.php");
    require_once(dirname(__FILE__) . "/../model/Airport.php");
    require_once(dirname(__FILE__) . "/../model/Stay.php");
    require_once(dirname(__FILE__) . "/../model/Flight.php");
    require_once(dirname(__FILE__) . "/../model/PublicHoliday.php");
    require_once(dirname(__FILE__) . "/../model/Fitness.php");

    class TripService {
        public function getRegularTrip($tripId) : ?Trip {
            $regularTrips = $this->doGetRegularTrips($tripId, NULL, array(TripIncludedEntity::Expenses->value, TripIncludedEntity::Stays->value,
                TripIncludedEntity::Flights->value, TripIncludedEntity::WatchedFlights->value, TripIncludedEntity::Layovers->value,
                TripIncludedEntity::Fitness->value, TripIncludedEntity::Notes->value, TripIncludedEntity::Highlights->value,
                TripIncludedEntity::Statistics->value, TripIncludedEntity::PublicHolidays->value));
            return count($regularTrips) === 1 ? $regularTrips[0] : NULL;
        }

        public function getTripsContainingInterval($start, $end) : array {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT DISTINCT trip_id FROM trip_summary WHERE start <= ? AND end >= ?")
                ->withParameters($start, $end)
                ->getMappedResultSet(function($tripRow) {
                    return $this->getRegularTrip($tripRow["trip_id"]);
                });
        }

        public function getOrCreateTripIdentifierForEntity($start, $end) : TripIdentifier {
            $regularTripIdentifier = $this->getTripIdentifierForEntity($start, $end);
            if ($regularTripIdentifier !== NULL) {
                return $regularTripIdentifier;
            }

            return $this->getOrCreateDayTripsTrip(date("Y", $start));
        }

        private function getTripIdentifierForEntity($start, $end) : ?TripIdentifier {
            global $databaseProvider;

            $tripId = $databaseProvider
                ->statementBuilder("SELECT trip_id FROM trip_event WHERE ? >= start AND ? <= end")
                ->withParameters(($start + $end) / 2, ($start + $end) / 2)
                ->getFirstColumn("trip_id");

            return $this->getTripIdentifierById($tripId);
        }

        public function getRegularTrips($year, $includedEntities) : array {
            return $this->doGetRegularTrips(NULL, $year, $includedEntities);
        }

        private function doGetRegularTrips($tripId, $year, $includedEntities) : array {
            global $databaseProvider, $statisticsService, $placeService, $expenseService,
                $stayService, $flightService, $fitnessService, $noteService, $highlightService;
            
            $trips = array();

            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($year !== NULL) {
                $whereClauseBuilder->withClause("year = ?", $year);
            }
            if ($tripId !== NULL) {
                $whereClauseBuilder->withClause("trip_id = ?", $tripId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $tripRows = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_summary {{WHERE CLAUSE}}", $whereClause)
                ->getResultSet();

            foreach ($tripRows as &$tripRow) {
                $countries = $placeService->getCountriesForTrip($tripRow["trip_id"]);
                
                $expenses = array();
                if (in_array(TripIncludedEntity::Expenses->value, $includedEntities)) {
                    $expenses = $expenseService->getExpensesForTrip($tripRow["trip_id"]);            
                }

                $stays = array();
                if (in_array(TripIncludedEntity::Stays->value, $includedEntities)) {
                    $stays = $stayService->getStaysForTrip($tripRow["trip_id"]);                        
                }

                $flights = array();
                if (in_array(TripIncludedEntity::Flights->value, $includedEntities)) {
                    $flights = $flightService->getFlightsForTrip($tripRow["trip_id"]);             
                }

                $watchedFlights = array();
                if (in_array(TripIncludedEntity::WatchedFlights->value, $includedEntities)) {
                    $watchedFlights = $flightService->getWatchedFlightsForTrip($tripRow["trip_id"]);
                }

                $layovers = array();
                if (in_array(TripIncludedEntity::Layovers->value, $includedEntities)) {
                    $layovers = $placeService->getLayoversForTrip($tripRow["trip_id"]);                   
                }

                $fitness = array();
                if (in_array(TripIncludedEntity::Fitness->value, $includedEntities)) {
                    $startOfDays = array();

                    $tripPlaces = $placeService->getRegularPlaces(NULL, $tripRow["trip_id"], NULL, NULL, NULL, NULL, array());
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
                        $fitness[] = $fitnessService->getFitnessRecordForDay($startOfDay);
                    }
                }

                $notes = array();
                if (in_array(TripIncludedEntity::Notes->value, $includedEntities)) {
                    $notes = $noteService->getNotesForTrip($tripRow["trip_id"]);                   
                }

                $highlights = array();
                if (in_array(TripIncludedEntity::Highlights->value, $includedEntities)) {
                    $highlights = $highlightService->getTripHighlights($tripRow["trip_id"]);        
                }

                $statistics = array();
                if (in_array(TripIncludedEntity::Statistics->value, $includedEntities)) {
                    $statistics = $statisticsService->getTripStatistics($tripRow["trip_id"]);                 
                }

                $publicHolidays = array();
                if (in_array(TripIncludedEntity::PublicHolidays->value, $includedEntities)) {
                    $publicHolidays = $this->getPublicHolidaysForTrip($tripRow["trip_id"], $countries);                               
                }

                $trips[] = new Trip($tripRow["trip_id"], $tripRow["name"], $tripRow["year"], $highlightService->getHighlight($tripRow["main_highlight_id"]), $tripRow["start"], $tripRow["end"], $countries,
                    $tripRow["cost"], $tripRow["days"], isset($tripRow["working_days"]) ? $tripRow["working_days"] : NULL, isset($tripRow["expected_vacation"]) ? $tripRow["expected_vacation"] : NULL,
                    isset($tripRow["max_vacation"]) ? $tripRow["max_vacation"] : NULL, $expenses, $stays, $flights, $watchedFlights, $layovers, $fitness, $notes, $highlights, $statistics, $publicHolidays);
            }

            return $trips;
        }

        public function updateDayTripsTripDates($tripId, $start, $end) : void {
            global $databaseProvider;

            $databaseProvider
                ->statementBuilder("UPDATE trip_day_trip SET start = ?, end = ? WHERE trip_id = ?")
                ->withParameters($start, $end, $tripId)
                ->execute();
        }

        private function getOrCreateDayTripsTrip($year) : TripIdentifier {
            global $databaseProvider, $configuration;

            $tripIdentifier = $this->getOrCreateTripIdentifier($configuration["specialTripNames"]["dayTrips"], $year);
            
            $tripRow = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_day_trip WHERE trip_id = ?")
                ->withParameters($tripIdentifier->getId())
                ->getSingleRow();
            
            if ($tripRow === NULL) {
                $databaseProvider
                    ->statementBuilder("INSERT INTO trip_day_trip (trip_id, start, end) VALUES (?, ?, ?)")
                    ->withParameters($tripIdentifier->getId(), strtotime("1.1." . $year), strtotime("31.12." . $year))
                    ->execute();
            }
            
            return $tripIdentifier;
        }

        public function getCandidateTrip($tripId) : ?Trip {
            return $this->doGetCandidateTrip($tripId, array(TripIncludedEntity::Notes->value), array(TripIncludedEntity::PublicHolidays->value));
        }

        public function getCandidateTrips($includedEntities) : array {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT DISTINCT trip_id FROM place_candidate_event")
                ->getMappedResultSet(function($tripRow) use (&$includedEntities) {
                    return $this->doGetCandidateTrip($tripRow["trip_id"], $includedEntities);
                });            
        }

        private function doGetCandidateTrip($tripId, $includedEntities) : ?Trip {
            global $databaseProvider, $noteService;

            $tripRow = $databaseProvider
                ->statementBuilder("SELECT ti.id, ti.name, tc.days, tc.countries FROM (SELECT trip_id, CEIL(MAX(end) / 86400) AS days, GROUP_CONCAT(DISTINCT ci.name SEPARATOR ',') AS countries FROM place_candidate_event pce INNER JOIN place_identifier pi ON pce.place_id = pi.id INNER JOIN category_identifier ci ON pi.country_category_id = ci.id GROUP BY pce.trip_id) tc INNER JOIN trip_identifier ti ON tc.trip_id = ti.id WHERE ti.id = ? ORDER BY ti.name")
                ->withParameters($tripId)
                ->getSingleRow();

            if ($tripRow === NULL) {
                return NULL;
            }

            $notes = array();
            if (in_array(TripIncludedEntity::Notes->value, $includedEntities)) {
                $notes = $noteService->getNotesForTrip($tripId);;
            }

            $holidays = array();
            if (in_array(TripIncludedEntity::PublicHolidays->value, $includedEntities)) {
                $holidays = $this->getPublicHolidaysForCountries(explode(",", $tripRow["countries"]));
            }

            return new Trip($tripRow["id"], $tripRow["name"], NULL, NULL, NULL, NULL, explode(",", $tripRow["countries"]), NULL, 
                $tripRow["days"], NULL, NULL, NULL, array(), array(), array(), array(), array(), array(), $notes, array(), array(), $holidays);
        }

        public function getTripIdentifier($name, $year) : ?TripIdentifier {
            global $databaseProvider, $highlightService;
            
            $tripIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_identifier WHERE name = ? AND year " . $databaseProvider->getIsNullOrEqualTo($year))
                ->withParameters($name)
                ->getFirstRow();

            if ($tripIdentifierRow === NULL) {
                return NULL;
            }

            return new TripIdentifier($tripIdentifierRow["id"], $tripIdentifierRow["name"], $tripIdentifierRow["year"],
                $highlightService->getHighlight($tripIdentifierRow["main_highlight_id"]));
        }

        public function getTripIdentifiersForDayTrips() : array {
            global $databaseProvider, $highlightService, $configuration;
            
            return $databaseProvider
                ->statementBuilder("SELECT * FROM trip_identifier WHERE name = ?")
                ->withParameters($configuration["specialTripNames"]["dayTrips"])
                ->getMappedResultSet(function($tripIdentifierRow) use(&$highlightService) {
                    return new TripIdentifier($tripIdentifierRow["id"], $tripIdentifierRow["name"], $tripIdentifierRow["year"],
                        $highlightService->getHighlight($tripIdentifierRow["main_highlight_id"]));
                });
        }

        public function getTripIdentifierById($tripId) : ?TripIdentifier {
            global $databaseProvider, $highlightService;
            
            $tripIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_identifier WHERE id = ?")
                ->withParameters($tripId)
                ->getSingleRow();

            if ($tripIdentifierRow === NULL) {
                return NULL;
            }

            return new TripIdentifier($tripIdentifierRow["id"], $tripIdentifierRow["name"], $tripIdentifierRow["year"],
                $highlightService->getHighlight($tripIdentifierRow["main_highlight_id"]));
        }

        public function updateTripMainHighlight($tripId, $highlightIdentifier) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE trip_identifier SET main_highlight_id = ? WHERE id = ?")
                ->withParameters($highlightIdentifier, $tripId)
                ->execute() === 1;
        }

        public function updateTripName($tripId, $name) : bool {
            global $databaseProvider, $googleApiClient, $eventPublisher;

            $wasUpdated = $databaseProvider
                ->statementBuilder("UPDATE trip_identifier SET name = ? WHERE id = ?")
                ->withParameters($name, $tripId)
                ->execute() === 1;

            $wasUpdated &= $googleApiClient->updateCalendarEventSummary("trips", $this->getTripEventId($tripId), $name);

            $eventPublisher->publishTripStatisticsChangedEvent($tripId);

            return $wasUpdated;
        }
        
        public function getOrCreateTripIdentifier($name, $year) : TripIdentifier { 
            global $databaseProvider, $yearService;

            $tripIdentifier = $this->getTripIdentifier($name, $year);
            if ($tripIdentifier !== NULL) {
                return $tripIdentifier;
            }
            
            // Make sure the year is registered so it can be used as a foreign key.
            if ($year !== NULL) {
                $yearService->getOrCreateYearIdentifier($year);
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO trip_identifier (name, year) VALUES (?, ?)")
                ->withParameters($name, $year)
                ->execute();
                
            return $this->getTripIdentifier($name, $year);
        }

        public function removeCandidateTrip($tripId) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("DELETE FROM place_candidate_event WHERE trip_id = ?")
                ->withParameters($tripId)
                ->execute();
        }

        public function moveTrip($tripId, $start) : Trip {
            global $googleApiClient, $placeService;
            
            $trip = $this->getRegularTrip($tripId);
            if ($trip === NULL) {
                throw new InvalidArgumentException("The trip " . $tripId . " could not be moved because it does not exist.");
            }

            $offset = $start - $trip->getStart();
            $placeService->movePlaces($tripId, $offset);
            $googleApiClient->updateCalendarEventDates("trips", $this->getTripEventId($tripId), $start, $offset + $trip->getEnd());

            return $trip;
        }

        public function loadTrip($candidateTripId, $targetTripId) : Trip {
            global $placeService, $noteService;

            $targetTrip = $this->getRegularTrip($targetTripId);
            if ($targetTrip === NULL) {
                throw new InvalidArgumentException("No places could not be loaded to the trip " . $targetTripId . " because it does not exist.");
            }

            $placeService->loadPlaces($candidateTripId, $targetTrip->getStart());
            $this->removeCandidateTrip($candidateTripId);

            $noteService->updateNotesOwner($candidateTripId, $targetTripId);
            
            return $targetTrip;
        }

        public function archiveTrip($tripId) : Trip {            
            global $noteService, $placeService;

            $trip = $this->getRegularTrip($tripId);
            if ($trip === NULL) {
                throw new InvalidArgumentException("The trip " . $tripId . " could not be archived because it does not exist.");
            }
            
            $archivedTripIdentifier = $this->getOrCreateTripIdentifier($trip->getName(), NULL);
            $noteService->createNote($archivedTripIdentifier->getId(), date("j.n.Y", $trip->getStart()) . " - " . date("j.n.Y", $trip->getEnd()));

            $placeService->archivePlaces($tripId, $trip->getStart(), $archivedTripIdentifier->getId());
            $this->deleteTripEvent($tripId);

            $noteService->updateNotesOwner($tripId, $archivedTripIdentifier->getId());
            
            return $this->getCandidateTrip($archivedTripIdentifier->getId());
        }

        private function getTripEventId($tripId) : ?string {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT id FROM trip_event WHERE trip_id = ?")
                ->withParameters($tripId)
                ->getSingleColumn("id");
        }
        
        private function deleteTripEvent($tripId) : bool {
            global $googleApiClient;
                
            return $googleApiClient->deleteCalendarEvent("trips", $this->getTripEventId($tripId));
        }
    
        private function getPublicHolidaysForCountries($countries) : array {
            $holidays = array();

            foreach ($countries as &$country) {
                foreach ($this->getPublicHolidaysForCountry($country) as &$holiday) {
                    $holidays[strtotime($holiday->getDate())] = $holiday;
                }
            }

            ksort($holidays);

            return array_values($holidays);
        }

        private function getPublicHolidaysForTrip($tripId, $countries) {
            global $tripService, $placeService;

            $holidays = array();

            foreach ($countries as &$country) {
                $countryHolidays = array();
                foreach ($tripService->getPublicHolidaysForCountry($country) as &$countryHoliday) {
                    $countryHolidays[$countryHoliday->getDate()] = $countryHoliday;
                }

                foreach ($placeService->getDatesForTripAndCountry($tripId, $country) as &$countryDate) {
                    if (array_key_exists($countryDate, $countryHolidays)) {
                        $holidays[] = new PublicHoliday($countryHolidays[$countryDate]->getName(), $country, $countryDate);
                    }
                }
            }

            return $holidays;
        }

        private function getPublicHolidaysForCountry($country) : array {
            global $calendarClient;

            $holidays = array();
            
            foreach ($calendarClient->getPublicHolidayEvents($country) as &$event) {               
                if ($event->getStart() > time()) {
                    $date = getdate($event->getStart());
                    $holidays[] = new PublicHoliday($event->getSummary(), $country, $date["mday"] . "." . $date["mon"] . "." . $date["year"]);                    
                }
            }

            return $holidays;
        }

        public function refreshCalendar() : void {
            global $databaseProvider, $calendarClient;
                
            $databaseProvider
                ->statementBuilder("DELETE FROM trip_event")
                ->execute();
            
            foreach ($calendarClient->getEvents("trips") as &$tripEvent) {
                $tripIdentifier = $this->getOrCreateTripIdentifier($tripEvent->getSummary(), date("Y", $tripEvent->getStart()));
                
                $databaseProvider
                    ->statementBuilder("INSERT INTO trip_event (id, trip_id, start, end) VALUES (?, ?, ?, ?)")
                    ->withParameters($tripEvent->getId(), $tripIdentifier->getId(), $tripEvent->getStart(), $tripEvent->getEnd())
                    ->execute();
            }
        }

        public function deleteAllDayTripsTrips() : void {
            global $databaseProvider;            
            
            $databaseProvider
                ->statementBuilder("DELETE FROM trip_day_trip")
                ->execute();
        }

        public function onCalendarChanged($message) {
            global $configuration, $placeService, $stayService, $flightService;

            // All calendars must be fetched as the entity trip ownership could change when adding/modifying/removing a trip.
            if ($message["calendar"] === "trips" && $message["watchId"] === $configuration["googleCalendarApi"]["watchId"]) {
                $this->deleteAllDayTripsTrips();
                $this->refreshCalendar();
                $placeService->refreshCalendar();
                $stayService->refreshCalendar();
                $flightService->refreshCalendar();
            }
        }
        
        public function onCalendarWatchRenewing($message) {
            global $calendarClient;

            if ($message["calendar"] === "trips") {
                $calendarClient->watchCalendar($message["calendar"], $message["watchId"]);
            }
        }
    }

    enum TripIncludedEntity : string {
        case Expenses = "EXPENSES";
        case Stays = "STAYS";
        case Flights = "FLIGHTS";
        case WatchedFlights = "WATCHED_FLIGHTS";
        case Layovers = "LAYOVERS";
        case Fitness = "FITNESS";
        case Notes = "NOTES";
        case Highlights = "HIGHLIGHTS";
        case Statistics = "STATISTICS";
        case PublicHolidays = "PUBLIC_HOLIDAYS";
    }
?>