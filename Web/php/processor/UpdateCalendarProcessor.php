<?php    
    require_once(dirname(__FILE__) . "/../ical.php");
    require_once(dirname(__FILE__) . "/GetCoordsProcessor.php");
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetPlaceIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetTripIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetPublicHolidaysProcessor.php");

    class UpdateCalendarProcessor extends Processor {
        public function process($input) {
            global $configuration, $databaseProvider;

            if ($input["uuid"] != $configuration["googleCalendarApiWatchUuid"]) {
                return FALSE;
            }

            // Create temporary tables for comparison of old and new data.
            $databaseProvider
                ->statementBuilder("DROP TEMPORARY TABLE IF EXISTS old_trip_event")
                ->execute();
            $databaseProvider
                ->statementBuilder("DROP TEMPORARY TABLE IF EXISTS old_place_event")
                ->execute();
            $databaseProvider
                ->statementBuilder("DROP TEMPORARY TABLE IF EXISTS old_stay_event")
                ->execute();
            $databaseProvider
                ->statementBuilder("DROP TEMPORARY TABLE IF EXISTS old_flight_event")
                ->execute();

            $databaseProvider
                ->statementBuilder("CREATE TEMPORARY TABLE old_trip_event AS SELECT * FROM trip_event")
                ->execute();
            $databaseProvider
                ->statementBuilder("CREATE TEMPORARY TABLE old_place_event AS SELECT p.*, ps.album_id, ps.category_ids FROM place_event p INNER JOIN _place_summary ps ON p.id = ps.id")
                ->execute();
            $databaseProvider
                ->statementBuilder("CREATE TEMPORARY TABLE old_stay_event AS SELECT * FROM stay_event")
                ->execute();
            $databaseProvider
                ->statementBuilder("CREATE TEMPORARY TABLE old_flight_event AS SELECT * FROM flight_event")
                ->execute();

            // Delete old data.
            $databaseProvider
                ->statementBuilder("DELETE FROM trip_event")
                ->execute();
            $databaseProvider
                ->statementBuilder("DELETE FROM place_event")
                ->execute();
            $databaseProvider
                ->statementBuilder("DELETE FROM stay_event")
                ->execute();
            $databaseProvider
                ->statementBuilder("DELETE FROM flight_event")
                ->execute();
            $databaseProvider
                ->statementBuilder("DELETE FROM flight_watched_event")
                ->execute();
            
            // Fill tables with new data.
            $this->processTrips();
            $this->processPlaces();
            $this->processStays();
            $this->processFlights();
            $this->processWatchedFlights();
            $this->processDayTrips();

            // Update references, re-compute stuff, etc.
            $this->postProcessTrips();
            $this->postProcessPlaces();
            $this->postProcessStays();
            $this->postProcessFlights();

            return TRUE;
        }

        public function getRequiredArguments() {
            return array("uuid");
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }

        // Processors.
        private function processTrips() {  
            global $databaseProvider, $configuration;

            $getTripIdentifierProcessor = new GetTripIdentifierProcessor();

            $holidays = $this->getHolidays();       
                     
            // Add trips to the database.
            foreach ($this->downloadEvents($configuration["calendars"]["trips"]) as &$tripEvent) {
                $name = $tripEvent["SUMMARY"];
                $year = intval(substr($tripEvent["DTSTART"], 0, 4));
                $tripIdentifier = $getTripIdentifierProcessor
                    ->process(array(
                        "name" => $name,
                        "year" => $year));

                $start = $this->getTimestamp($tripEvent["DTSTART"]);
                $end = $this->getTimestamp($tripEvent["DTEND"]);
                
                $databaseProvider
                    ->statementBuilder("INSERT INTO trip_event (id, trip_id, start, end) VALUES (?, ?, ?, ?)")
                    ->withParameters($tripEvent["UID"], $tripIdentifier->getId(), $start, $end)
                    ->execute();
            }
        }

        private function processPlaces() {
            global $databaseProvider, $configuration;

            $getCoordsProcessor = new GetCoordsProcessor();
            $getPlaceIdentifierProcessor = new GetPlaceIdentifierProcessor();

            // Add places to the database.
            foreach ($this->downloadEvents($configuration["calendars"]["places"]) as &$placeEvent) {
                $name = html_entity_decode($placeEvent["SUMMARY"], ENT_QUOTES | ENT_HTML5);
                $address = html_entity_decode(str_replace('\\', '', $placeEvent["LOCATION"]), ENT_QUOTES | ENT_HTML5);
                $resolvedLocation = $getCoordsProcessor
                    ->process(array(
                        "address" => $address));
                $placeIdentifier = $getPlaceIdentifierProcessor
                    ->process(array(
                        "name" => $name,
                        "country" => $resolvedLocation->getCountry(),
                        "address" => $address));
                        
                $timeOffset = $this->getTimezoneOffset($placeEvent["DTSTART"], $placeIdentifier->getTimezone());
                $start = $this->getTimestamp($placeEvent["DTSTART"]) - $timeOffset;
                $end = $this->getTimestamp($placeEvent["DTEND"]) - $timeOffset;     

                $placeEventDescription = $this->getEventDescription($placeEvent);
                $isLayover = array_key_exists("Layover", $placeEventDescription);
                        
                $databaseProvider
                    ->statementBuilder("INSERT INTO place_event (id, place_id, trip_id, start, end, layover) VALUES (?, ?, GET_TRIP_ID_FOR_INTERVAL(?, ?), ?, ?, ?)")
                    ->withParameters($placeEvent["UID"], $placeIdentifier->getId(), $start, $end, $start, $end, $isLayover ? 1 : 0)
                    ->execute();

                // Update address to match a common format.
                // When changing the format, do not forget to update it in GetCoordsProcessor/LoadTripProcessor as well.
                $newAddress = $name . ", " . $resolvedLocation->getCountry() . " (" . $resolvedLocation->getLatitude() . ", " . $resolvedLocation->getLongitude() . ")";
                if (str_replace(' ', '', $address) != str_replace(' ', '', $newAddress)) {
                    $this->updatePlaceEventLocation($placeEvent["UID"], $newAddress);
                }
            }
        }

        private function processStays() {
            global $databaseProvider, $configuration;

            // Add stays to the database.
            foreach ($this->downloadEvents($configuration["calendars"]["stays"]) as &$stayEvent) {
                $name = str_replace('\\', '', html_entity_decode($stayEvent["SUMMARY"], ENT_QUOTES | ENT_HTML5));
                $address = str_replace('\\', '', $stayEvent["LOCATION"]);

                $start = $this->getTimestamp($stayEvent["DTSTART"]);
                $end = $this->getTimestamp($stayEvent["DTEND"]); 

                $databaseProvider
                    ->statementBuilder("INSERT INTO stay_event (id, name, trip_id, start, end, address) VALUES (?, ?, GET_TRIP_ID_FOR_INTERVAL(?, ?), ?, ?, ?)")
                    ->withParameters($stayEvent["UID"], $name, $start, $end, $start, $end, $address)
                    ->execute();
            }
        }

        private function processFlights() {
            $this->doProcessFlights("flights", "flight_event");
        }

        private function processWatchedFlights() {
            $this->doProcessFlights("watchedFlights", "flight_watched_event");
        }

        private function doProcessFlights($calendar, $table) {
            global $databaseProvider, $configuration;

            // Add flights to the database.
            foreach ($this->downloadEvents($configuration["calendars"][$calendar]) as &$flightEvent) {
                preg_match("{(.+) - (.+) \((.+)\)}", $flightEvent["SUMMARY"], $tokens);
                
                $from = $tokens[1];
                $to = $tokens[2];
                $flight = str_replace(" ", "", $tokens[3]);

                $start = $this->getTimestamp($flightEvent["DTSTART"]);
                $end = $this->getTimestamp($flightEvent["DTEND"]);

                $databaseProvider
                    ->statementBuilder("INSERT INTO " . $table . " (id, flight, trip_id, start, end, `from`, `to`) VALUES (?, ?, GET_TRIP_ID_FOR_INTERVAL(?, ?), ?, ?, ?, ?)")
                    ->withParameters($flightEvent["UID"], $flight, $start, $end, $start, $end, $from, $to)
                    ->execute();
            }
        }

        private function processDayTrips() {
            global $configuration, $databaseProvider;

            $getTripIdentifierProcessor = new GetTripIdentifierProcessor();

            // Add day trips to the database.
            $years = $databaseProvider
                // This does not pick up years for which there is, e.g., a flight, but no place. But it doesn't really make much sense, flying somewhere not to visit anything, so we can live with it.
                ->statementBuilder("SELECT DISTINCT DATE_FORMAT(FROM_UNIXTIME(start), '%Y') AS year FROM place_event WHERE trip_id IS NULL ORDER BY year")
                ->getResultSetForColumn("year");

            foreach ($years as &$year) {
                $tripIdentifier = $getTripIdentifierProcessor
                    ->process(array(
                        "name" => $configuration["specialTripNames"]["dayTrips"],
                        "year" => $year));

                $databaseProvider
                    ->statementBuilder("INSERT INTO trip_event (trip_id, start, end) VALUES (?, 0, 2147483647)")
                    ->withParameters($tripIdentifier->getId())
                    ->execute();

                $databaseProvider
                    ->statementBuilder("UPDATE place_event SET trip_id = ? WHERE trip_id IS NULL AND DATE_FORMAT(FROM_UNIXTIME(start), '%Y') = ?")
                    ->withParameters($tripIdentifier->getId(), $year)
                    ->execute();

                $databaseProvider
                    ->statementBuilder("UPDATE stay_event SET trip_id = ? WHERE trip_id IS NULL AND DATE_FORMAT(FROM_UNIXTIME(start), '%Y') = ?")
                    ->withParameters($tripIdentifier->getId(), $year)
                    ->execute();

                $databaseProvider
                    ->statementBuilder("UPDATE flight_event SET trip_id = ? WHERE trip_id IS NULL AND DATE_FORMAT(FROM_UNIXTIME(start), '%Y') = ?")
                    ->withParameters($tripIdentifier->getId(), $year)
                    ->execute();

                $databaseProvider
                    ->statementBuilder("UPDATE flight_watched_event SET trip_id = ? WHERE trip_id IS NULL AND DATE_FORMAT(FROM_UNIXTIME(start), '%Y') = ?")
                    ->withParameters($tripIdentifier->getId(), $year)
                    ->execute();
                    
                $databaseProvider
                    ->statementBuilder("UPDATE trip_event SET start = (SELECT MIN(start) FROM place_event WHERE trip_id = ?) WHERE trip_id = ?")
                    ->withParameters($tripIdentifier->getId(), $tripIdentifier->getId())
                    ->execute();
                    
                $databaseProvider
                    ->statementBuilder("UPDATE trip_event SET end = (SELECT MAX(start) FROM place_event WHERE trip_id = ?) WHERE trip_id = ?")
                    ->withParameters($tripIdentifier->getId(), $tripIdentifier->getId())
                    ->execute();                
            }
        }
        
        // Post-processors.
        private function postProcessTrips() {
            global $databaseProvider, $schedulingProvider, $configuration;

            // Process overlapping trips.
            $overlappingTripName = $databaseProvider
                ->statementBuilder("SELECT GET_FULLY_QUALIFIED_TRIP_NAME_FROM_TRIP_ID(te1.trip_id) AS trip FROM trip_event te1 WHERE te1.start < (SELECT MAX(te2.end) FROM trip_event te2 WHERE te2.start < te1.start AND te2.id IS NOT NULL)")
                ->getFirstColumn("trip");

            if ($overlappingTripName != NULL) {
                // If there was an error, e.g., when moving a trip and two trips overlap now, don't update the calendar tables so that the action can be reverted.
                throw new RuntimeException("The trip " . $overlappingTripName . " overlaps with the previous one.");
            }

            // Recompute expected time off to use until a fixed point is reached.
            $cachedTotalTimeOffToUse = $databaseProvider
                ->statementBuilder("SELECT SUM(expected_vacation) AS sum FROM trip_summary")
                ->getSingleColumn("sum");
            
            $actualTotalTimeOffToUse = $databaseProvider
                ->statementBuilder("SELECT SUM(expected_vacation) AS sum FROM _trip_summary")
                ->getSingleColumn("sum");

            if ($cachedTotalTimeOffToUse != $actualTotalTimeOffToUse) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateCalendar", array(
                        "uuid" => $configuration["googleCalendarApiWatchUuid"]), NULL);
            }
        }

        private function postProcessPlaces() {
            global $databaseProvider, $configuration, $schedulingProvider;
            
            // Process new places, renamed places and places for which the start time has changed.
            $newPlaceRows = $databaseProvider
                ->statementBuilder("SELECT np.start, np.place_id, np.trip_id FROM place_event np LEFT JOIN old_place_event op ON op.id = np.id WHERE op.place_id <> np.place_id OR op.start <> np.start")
                ->getResultSet();

            foreach ($newPlaceRows as &$newPlaceRow) {
                if (time() < $newPlaceRow["start"]) {
                    if (time() + $configuration["forecastDaysToCache"] * 86400 > $newPlaceRow["start"]) {
                        $schedulingProvider
                            ->scheduleJobExecution("UpdateActualForecast", array(
                                "placeId" => $newPlaceRow["place_id"],
                                "start" => $newPlaceRow["start"]), NULL);
                    }
                            
                    $schedulingProvider
                        ->scheduleJobExecution("UpdateHistoricalForecast", array(
                            "placeId" => $newPlaceRow["place_id"],
                            "start" => $newPlaceRow["start"]), NULL);

                    $schedulingProvider
                        ->scheduleJobExecution("UpdateDaylightForecast", array(
                            "placeId" => $newPlaceRow["place_id"],
                            "start" => $newPlaceRow["start"]), NULL);
                }

                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $newPlaceRow["trip_id"]), NULL);
            }

            // Process yet non-visited places.
            $nonVisitedPlaceRows = $databaseProvider
                ->statementBuilder("SELECT DISTINCT place_id FROM place_event WHERE place_id NOT IN (SELECT DISTINCT place_id FROM place_event WHERE end < UNIX_TIMESTAMP())")
                ->getResultSet();

            foreach ($nonVisitedPlaceRows as &$nonVisitedPlaceRow) {
                $databaseProvider
                    ->statementBuilder("DELETE FROM place_candidate WHERE place_id = ?")
                    ->withParameters($nonVisitedPlaceRow["place_id"])
                    ->execute();

                $databaseProvider
                    ->statementBuilder("INSERT INTO place_candidate (place_id) VALUES (?)")
                    ->withParameters($nonVisitedPlaceRow["place_id"])
                    ->execute();
            }

            // Process removed places.
            $removedPlaceRows = $databaseProvider
                ->statementBuilder("SELECT op.trip_id, op.category_ids FROM old_place_event op LEFT JOIN place_event np ON op.id = np.id WHERE np.id IS NULL")
                ->getResultSet();

            foreach ($removedPlaceRows as &$removedPlaceRow) {
                if ($removedPlaceRow["trip_id"] != NULL) {
                    $schedulingProvider
                        ->scheduleJobExecution("UpdateStats", array(
                            "type" => "TRIP", 
                            "id" => $removedPlaceRow["trip_id"]), NULL);
                }
                
                if ($removedPlaceRow["category_ids"] != NULL) {
                    foreach (explode(",", $removedPlaceRow["category_ids"]) as &$categoryId) {
                        $schedulingProvider
                            ->scheduleJobExecution("UpdateStats", array(
                                "type" => "CATEGORY", 
                                "id" => $categoryId), NULL);
                    }
                }
            }

            // Unhide countries in the configuration.
            $databaseProvider
                ->statementBuilder("UPDATE configuration SET levels = 'public,modifiable' WHERE type = 'COUNTRIES' AND `key` IN (SELECT country FROM place_identifier)")
                ->execute();
        }

        private function postProcessStays() {
            global $databaseProvider, $schedulingProvider, $configuration;

            // Process new and renamed stays.
            $newStayRows = $databaseProvider
                ->statementBuilder("SELECT ns.trip_id FROM stay_event ns LEFT JOIN old_stay_event os ON os.id = ns.id WHERE os.name IS NULL")
                ->getResultSet();

            foreach ($newStayRows as &$newStayRow) {                  
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $newStayRow["trip_id"]), NULL);
            }

            // Process removed stays.
            $removedStayRows = $databaseProvider
                ->statementBuilder("SELECT os.trip_id FROM old_stay_event os LEFT JOIN stay_event ns ON os.id = ns.id WHERE ns.id IS NULL")
                ->getResultSet();

            foreach ($removedStayRows as &$removedStayRow) {
                if ($removedStayRow["trip_id"] != NULL) {
                    $schedulingProvider
                        ->scheduleJobExecution("UpdateStats", array(
                            "type" => "TRIP", 
                            "id" => $removedStayRow["trip_id"]), NULL);
                }
            }
        }

        private function postProcessFlights() {
            global $databaseProvider, $schedulingProvider, $configuration;

            // Process new and renamed flights.
            $newFlightRows = $databaseProvider
                ->statementBuilder("SELECT nf.trip_id FROM flight_event nf LEFT JOIN old_flight_event of ON of.id = nf.id WHERE of.flight IS NULL")
                ->getResultSet();
            
            foreach ($newFlightRows as &$newFlightRow) {              
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $newFlightRow["trip_id"]), NULL);
            }

            // Add unknown operators to the configuration.
            $databaseProvider
                ->statementBuilder("INSERT INTO configuration (type, levels, `key`, value) SELECT DISTINCT 'AIRLINES', 'public,modifiable', SUBSTR(flight, 1, 2), SUBSTR(flight, 1, 2) FROM flight_event f LEFT JOIN (SELECT `key` AS code FROM configuration WHERE type = 'AIRLINES') c ON SUBSTR(f.flight, 1, 2) = c.code WHERE c.code IS NULL")
                ->execute();
            $databaseProvider
                ->statementBuilder("INSERT INTO configuration (type, levels, `key`, value) SELECT DISTINCT 'AIRLINES', 'public,modifiable', SUBSTR(flight, 1, 2), SUBSTR(flight, 1, 2) FROM flight_watched_event f LEFT JOIN (SELECT `key` AS code FROM configuration WHERE type = 'AIRLINES') c ON SUBSTR(f.flight, 1, 2) = c.code WHERE c.code IS NULL")
                ->execute();

            // Process removed flights.
            $removedFlightRows = $databaseProvider
                ->statementBuilder("SELECT of.trip_id FROM old_flight_event of LEFT JOIN flight_event nf ON of.id = nf.id WHERE nf.id IS NULL")
                ->getResultSet();

            foreach ($removedFlightRows as &$removedFlightRow) {
                if ($removedFlightRow["trip_id"] != NULL) {
                    $schedulingProvider
                        ->scheduleJobExecution("UpdateStats", array(
                            "type" => "TRIP", 
                            "id" => $removedFlightRow["trip_id"]), NULL);
                }
            }
        }

        // Helper functions.
        private function updatePlaceEventLocation($event, $newAddress) {
            $eventId = explode("@", $event)[0];
            $calendarId = (new GetCalendarIdentifierProcessor())
                ->process(array(
                    "name" => "places"));
                    
            return (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "PATCH",
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events/" . $eventId, 
                    "payload" => json_encode(array(
                        "location" => $newAddress))));
        }

        private function getHolidays() {
            global $configuration, $databaseProvider;

            $holidays = (new GetPublicHolidaysProcessor())
                ->process(array(
                    "country" => $configuration["homeLocation"]["country"]));

            // Update holidays in the configuration for the use in SQL functions.
            $databaseProvider
                ->statementBuilder("UPDATE configuration SET value = ? WHERE type = 'PUBLIC_HOLIDAYS'")
                ->withParameters(implode(",", array_map(function($holiday) { return $holiday->getDate(); }, $holidays)))
                ->execute();

            return $holidays;
        }

        private function downloadEvents($url) {
            $data = file_get_contents($url);
            if ($data == FALSE) {
                throw new RuntimeException("Unable to download file from " . $url . ".");
            }
            $ical = new ICal(explode("\n", $data));
            return isset($ical->cal["VEVENT"]) ? $ical->cal["VEVENT"] : array();
        }

        private function getEventDescription($event) {
            if (!array_key_exists("DESCRIPTION", $event)) {
                return array();
            }

            $description = array();
            $descriptionEntries = explode("\\n", $event["DESCRIPTION"]);

            foreach ($descriptionEntries as &$descriptionEntry) {
                $tokens = explode(":", $descriptionEntry);
                $key = trim($tokens[0]);
                $value = (count($tokens) == 1) ? "" : trim(str_replace("\xc2\xa0", " ", $tokens[1]));
                $description[$key] = ($value == "") ? NULL : $value;
            }

            return $description;
        }

        private function getTimestamp($date) {
            global $configuration;

            return (new DateTime($date, new DateTimeZone($configuration["homeLocation"]["timezone"])))->getTimestamp();
        }        

        private function getTimezoneOffset($timestamp, $fromTimezone) {
            global $configuration;

            $timezone = new DateTimeZone($fromTimezone);
            $dateTimeAtHome = new DateTime($timestamp, new DateTimeZone($configuration["homeLocation"]["timezone"]));
            return $timezone->getOffset($dateTimeAtHome) - (new DateTimeZone($configuration["homeLocation"]["timezone"]))->getOffset($dateTimeAtHome);
        }
    }
?>