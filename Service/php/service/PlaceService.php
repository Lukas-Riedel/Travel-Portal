<?php
    require_once(dirname(__FILE__) . "/../model/Weather.php");
    require_once(dirname(__FILE__) . "/../model/Sun.php");
    require_once(dirname(__FILE__) . "/../model/PlaceIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Place.php");
    require_once(dirname(__FILE__) . "/../model/Album.php");
    require_once(dirname(__FILE__) . "/../model/Date.php");
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/TripIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Highlight.php");

    class PlaceService {
        public function getDatesForTripAndCountry($tripId, $country) : array {
            global $databaseProvider;
            
            return $databaseProvider
                ->statementBuilder("SELECT DISTINCT DATE_FORMAT(FROM_UNIXTIME(start),'%e.%c.%Y') AS date FROM place_summary WHERE trip_id = ? AND country = ?")
                ->withParameters($tripId, $country)
                ->getResultSetForColumn("date");
        }

        public function getCountriesForTrip($tripId) : array {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT DISTINCT country FROM place_summary WHERE trip_id = ? AND NOT layover GROUP BY country ORDER BY MIN(start)")
                ->withParameters($tripId)
                ->getResultSetForColumn("country");
        }

        public function getLayoversForTrip($tripId) : array {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT place_id FROM place_summary WHERE trip_id = ? AND layover = 1")
                ->withParameters($tripId)
                ->getResultSetForColumn("place_id");
        }

        public function getRegularPlace($placeId) : ?Place {
            $regularPlaces = $this->doGetRegularPlaces($placeId, NULL, NULL, NULL, NULL, NULL, NULL, TRUE, TRUE, TRUE);
            return count($regularPlaces) === 1 ? $regularPlaces[0] : NULL;
        }

        public function getRegularPlaces($categoryId, $tripId, $year, $albumId, $minStart, $maxEnd, $includeCategories, $includeHighlights, $includeExcerpt) : array {
            return $this->doGetRegularPlaces(NULL, $categoryId, $tripId, $year, $albumId, $minStart, $maxEnd, $includeCategories, $includeHighlights, $includeExcerpt);
        }

        private function doGetRegularPlaces($placeId, $categoryId, $tripId, $year, $albumId, $minStart, $maxEnd, $includeCategories, $includeHighlights, $includeExcerpt) : array {            
            global $databaseProvider, $highlightService, $categoryService, $albumService, $tripService, $forecastService;
            
            $places = array();

            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($placeId !== NULL) {
                $whereClauseBuilder->withClause("place_id = ?", $placeId);
            }
            if ($year !== NULL) {
                $whereClauseBuilder->withClause("DATE_FORMAT(FROM_UNIXTIME(start), '%Y') = ?", $year);
            }
            if ($tripId !== NULL) {
                $whereClauseBuilder->withClause("trip_id = ?", $tripId);
            }
            if ($albumId !== NULL) {
                $whereClauseBuilder->withClause("album_id = ?", $albumId);
            }
            if ($categoryId !== NULL) {
                $whereClauseBuilder->withClause("(FIND_IN_SET(?, category_ids) OR ((UNIX_TIMESTAMP() - GET_VARIABLE_TIME_CATEGORY_OFFSET(?) <= start) AND (UNIX_TIMESTAMP() >= end)) OR ((GET_VARIABLE_TIME_CATEGORY_OFFSET(?) IS NOT NULL) AND (place_id IN (SELECT place_id FROM place_permanent))))", $categoryId, $categoryId, $categoryId);
            }
            if ($minStart !== NULL) {
                $whereClauseBuilder->withClause("? <= start", $minStart);
            }
            if ($maxEnd !== NULL) {
                $whereClauseBuilder->withClause("end <= ?", $maxEnd);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            $placeRows = $databaseProvider
                ->statementBuilder("SELECT * FROM place_summary {{WHERE CLAUSE}} ORDER BY start", $whereClause)
                ->getResultSet();

            foreach ($placeRows as &$placeRow) {
                if (!isset($places[$placeRow["place_id"]])) {
                    $categories = array();
                    if ($includeCategories) {
                        $categories = $categoryService->getCategoryIdentifiers(explode(",", $placeRow["category_ids"]));
                    }                   

                    $highlights = array();             
                    if ($includeHighlights) {
                        $highlights = $highlightService->getPlaceHighlights($placeRow["place_id"]);                      
                    }
                    
                    $excerpt = NULL;
                    if ($includeExcerpt) {
                        $excerpt = $placeRow["excerpt"];
                    }
                    
                    $places[$placeRow["place_id"]] = new Place($placeRow["place_id"], $placeRow["name"], $placeRow["country"], $placeRow["latitude"], $placeRow["longitude"], $placeRow["timezone"],
                        $highlightService->getHighlight($placeRow["main_highlight_id"]), $excerpt, $categories, $highlights, array());
                }
                
                $weather = NULL;
                $sun = NULL;
                if ($placeRow["end"] > time()) {
                    $weather = $forecastService->getWeatherForecast($placeRow["place_id"], $placeRow["start"]);
                    $sun = $forecastService->getSunForecast($placeRow["place_id"], $placeRow["start"]);
                }

                $album = $albumService->getAlbum($placeRow["album_id"]);    
                $trip = $tripService->getTripIdentifierById($placeRow["trip_id"]);

                $places[$placeRow["place_id"]]->addDate(new Date($placeRow["start"], $placeRow["end"], $weather, $sun, $album, $trip));  
            }

            // Process permanent places without dates.
            if ($tripId === NULL && $albumId === NULL) {
                $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
                if ($placeId !== NULL) {
                    $whereClauseBuilder->withClause("pi.id = ?", $placeId);
                }
                if ($categoryId !== NULL) {
                    $whereClauseBuilder->withClause("FIND_IN_SET(?, cs.category_ids)", $categoryId);
                }
                $whereClause = $whereClauseBuilder->withClause("pi.id NOT IN (SELECT place_id FROM place_summary)")->buildForAnd();

                $placeRows = $databaseProvider
                    ->statementBuilder("SELECT pi.*, COALESCE(cs.category_ids, '') AS category_ids FROM place_permanent pp INNER JOIN place_identifier pi ON pp.place_id = pi.id LEFT JOIN category_summary cs ON pi.id = cs.place_id {{WHERE CLAUSE}}", $whereClause)
                    ->getResultSet();

                foreach ($placeRows as &$placeRow) {
                    if (!isset($places[$placeRow["place_id"]])) {
                        $categories = array();
                        if ($includeCategories) {
                            $categories = $categoryService->getCategoryIdentifiers(explode(",", $placeRow["category_ids"]));
                        }                   

                        $highlights = array();             
                        if ($includeHighlights) {
                            $highlights = $highlightService->getPlaceHighlights($placeRow["place_id"]);                      
                        }
                        
                        $excerpt = NULL;
                        if ($includeExcerpt) {
                            $excerpt = $placeRow["excerpt"];
                        }
                        
                        $places[$placeRow["place_id"]] = new Place($placeRow["place_id"], $placeRow["name"], $placeRow["country"], $placeRow["latitude"], $placeRow["longitude"], $placeRow["timezone"],
                            $highlightService->getHighlight($placeRow["main_highlight_id"]), $excerpt, $categories, $highlights, array());
                    }
                }
            }

            return array_values($places);
        }

        public function getCandidatePlace($placeId) : ?Place {
            $candidatePlaces = $this->doGetCandidatePlaces($placeId, NULL, TRUE, TRUE, TRUE);
            return count($candidatePlaces) === 1 ? $candidatePlaces[0] : NULL;
        }

        public function getCandidatePlaces($categoryId, $tripId, $includeHighlights, $includeCategories, $includeExcerpt) : array {
            return $tripId !== NULL
                ? $this->doGetCandidatePlacesForTrip($categoryId, $tripId, $includeHighlights, $includeCategories, $includeExcerpt)
                : $this->doGetCandidatePlaces(NULL, $categoryId, $includeHighlights, $includeCategories, $includeExcerpt);
        }
        
        private function doGetCandidatePlaces($placeId, $categoryId, $includeHighlights, $includeCategories, $includeExcerpt) : array {
            global $databaseProvider, $tripService, $albumService, $highlightService, $categoryService;

            $whereClauseBuilder = $databaseProvider->whereClauseBuilder(); 
            if ($placeId !== NULL) {
                $whereClauseBuilder->withClause("cs.place_id = ?", $placeId);
            }
            if ($categoryId !== NULL) {
                $whereClauseBuilder->withClause("FIND_IN_SET(?, category_ids)", $categoryId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $placeRows = $databaseProvider
                ->statementBuilder("SELECT pcan.*, cs.category_ids FROM (SELECT place_id, name, country, latitude, longitude, timezone, main_highlight_id, excerpt FROM place_candidate pc INNER JOIN place_identifier pi ON pc.place_id = pi.id UNION SELECT place_id, name, country, latitude, longitude, timezone, main_highlight_id, excerpt FROM place_candidate_event pce INNER JOIN place_identifier pi ON pce.place_id = pi.id UNION SELECT ps.place_id, ps.name, ps.country, ps.latitude, ps.longitude, ps.timezone, ps.main_highlight_id, ps.excerpt FROM place_event p INNER JOIN place_summary ps ON p.place_id = ps.place_id WHERE ps.start < UNIX_TIMESTAMP() GROUP BY ps.name, ps.country HAVING (MAX(ps.start) < UNIX_TIMESTAMP() - GET_CONFIGURATION('DAYS_BEFORE_APPEARING_IN_PLAN') * 86400) OR MAX(ps.album_id) IS NULL) pcan INNER JOIN category_summary cs ON pcan.place_id = cs.place_id {{WHERE CLAUSE}} ORDER BY country, name", $whereClause)
                ->getResultSet();

            $places = array();
            foreach ($placeRows as &$placeRow) {
                $dateRows = $databaseProvider
                    ->statementBuilder("SELECT * FROM place_summary WHERE place_id = ? AND start < UNIX_TIMESTAMP()")
                    ->withParameters($placeRow["place_id"])
                    ->getResultSet();
                    
                $dates = array();
                foreach ($dateRows as &$dateRow) {    
                    $album = $albumService->getAlbum($dateRow["album_id"]);    
                    $trip = $tripService->getTripIdentifierById($dateRow["trip_id"]);
                    $dates[] = new Date($dateRow["start"], $dateRow["end"], NULL, NULL, $album, $trip);
                }
                
                $highlights = array();
                if ($includeHighlights) {
                    $highlights = $highlightService->getPlaceHighlights($placeRow["place_id"]);                      
                }
                
                $excerpt = NULL;
                if ($includeExcerpt) {
                    $excerpt = $placeRow["excerpt"];
                }

                $categories = array();
                if ($includeCategories) {
                    $categories = $categoryService->getCategoryIdentifiers(explode(",", $placeRow["category_ids"]));
                }

                $places[] = new Place($placeRow["place_id"], $placeRow["name"], $placeRow["country"], $placeRow["latitude"], $placeRow["longitude"], $placeRow["timezone"],
                    $highlightService->getHighlight($placeRow["main_highlight_id"]), $excerpt, $categories, $highlights, $dates);                
            }
            
            return $places;
        }

        private function doGetCandidatePlacesForTrip($categoryId, $tripId, $includeHighlights, $includeCategories, $includeExcerpt) {
            global $databaseProvider, $tripService, $highlightService, $categoryService;
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder(); 
            if ($categoryId !== NULL) {
                $whereClauseBuilder->withClause("FIND_IN_SET(?, category_ids)", $categoryId);
            }
            $whereClause = $whereClauseBuilder->withClause("trip_id = ?", $tripId)->buildForAnd();

            $placeRows = $databaseProvider
                ->statementBuilder("SELECT pce.place_id, pi.name, pi.country, pi.latitude, pi.longitude, pi.timezone, pi.main_highlight_id, pi.excerpt, pce.start, pce.end, cs.category_ids FROM place_candidate_event pce INNER JOIN place_identifier pi ON pce.place_id = pi.id INNER JOIN category_summary cs ON pi.id = cs.place_id {{WHERE CLAUSE}}", $whereClause)
                ->getResultSet();

            $places = array();            
            foreach ($placeRows as &$placeRow) {
                if (!isset($places[$placeRow["place_id"]])) {
                    $highlights = array();
                    if ($includeHighlights) {
                        $highlights = $highlightService->getPlaceHighlights($placeRow["place_id"]);                      
                    }
                    
                    $excerpt = NULL;
                    if ($includeExcerpt) {
                        $excerpt = $placeRow["excerpt"];
                    }

                    $categories = array();
                    if ($includeCategories) {
                        $categories = $categoryService->getCategoryIdentifiers(explode(",", $placeRow["category_ids"]));
                    }

                    $places[$placeRow["place_id"]] = new Place($placeRow["place_id"], $placeRow["name"], $placeRow["country"], $placeRow["latitude"], $placeRow["longitude"], $placeRow["timezone"],
                        $highlightService->getHighlight($placeRow["main_highlight_id"]), $excerpt, $categories, $highlights, array()); 
                }

                $trip = $tripService->getTripIdentifierById($tripId);                
                $places[$placeRow["place_id"]]->addDate(new Date($placeRow["start"], $placeRow["end"], NULL, NULL, NULL, $trip));
            }

            return array_values($places);
        }

        public function getPlaceIdentifier($name, $country) : ?PlaceIdentifier {
            global $databaseProvider, $highlightService;

            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE name = ? AND country = ?")
                ->withParameters($name, $country)
                ->getFirstRow();

            if ($placeIdentifierRow === NULL) {
                return NULL;
            }

            if ($placeIdentifierRow["excerpt"] === NULL) {
                $databaseProvider
                    ->statementBuilder("UPDATE place_identifier SET excerpt = ? WHERE id = ?")
                    ->withParameters($this->getSuggestedExcerpt($name, $country), $placeIdentifierRow["id"])
                    ->execute();

                $placeIdentifierRow = $databaseProvider
                    ->statementBuilder("SELECT * FROM place_identifier WHERE name = ? AND country = ?")
                    ->withParameters($name, $country)
                    ->getFirstRow();
            }

            return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
        }

        public function getPlaceIdentifierById($placeId) : ?PlaceIdentifier {
            global $databaseProvider, $highlightService;
            
            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($placeId)
                ->getSingleRow();

            if ($placeIdentifierRow === NULL) {
                return NULL;
            }
            
            return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
        }

        public function updatePlaceMainHighlight($placeId, $highlightIdentifier) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE place_identifier SET main_highlight_id = ? WHERE id = ?")
                ->withParameters($highlightIdentifier, $placeId)
                ->execute() === 1;
        }

        public function updatePlaceExcerpt($placeId, $excerpt) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE place_identifier SET excerpt = ? WHERE id = ?")
                ->withParameters($excerpt, $placeId)
                ->execute() === 1;
        }

        public function updatePlaceName($placeId, $name) : bool {
            global $databaseProvider, $googleApiClient, $albumService, $schedulingProvider;

            $wasUpdated = $databaseProvider
                ->statementBuilder("UPDATE place_identifier SET name = ?, excerpt = NULL WHERE id = ?")
                ->withParameters($name, $placeId)
                ->execute() === 1;

            $place = $this->getRegularPlace($placeId);
            if ($place !== NULL) {
                foreach ($place->getDates() as &$date) {                       
                    $album = $date->getAlbum();
                    if ($album !== NULL) {     
                        $externalAlbumId = $albumService->getExternalIdentifier($album->getId());
                        $wasUpdated &= $googleApiClient->updateAlbumName($externalAlbumId, str_replace($place->getName(), $name, $album->getName()));
                        $albumService->updateAlbum($album->getId());
                    }

                    $eventId = $this->getPlaceEventId($placeId, $date->getStart());
                    if ($eventId !== NULL) {  
                        $wasUpdated &= $googleApiClient->updateCalendarEventSummary("places", $eventId, $name);
                    }
                }
            }
            
            foreach ($this->getContainedTripIdentifiers($placeId) as &$tripId) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => StatisticsType::Trip->value, 
                        "id" => $tripId), NULL); 
            }   

            return $wasUpdated;
        }

        public function updatePlaceLocation($placeId, $latitude, $longitude) : bool {
            global $databaseProvider, $schedulingProvider;

            $wasUpdated = $databaseProvider
                ->statementBuilder("UPDATE place_identifier SET latitude = ?, longitude = ? WHERE id = ?")
                ->withParameters($latitude, $longitude, $placeId)
                ->execute() === 1;
            
            foreach ($this->getContainedTripIdentifiers($placeId) as &$tripId) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => StatisticsType::Trip->value, 
                        "id" => $tripId), NULL); 
            }   

            return $wasUpdated;
        }

        public function getAllPlaceIdentifiers() : array {
            global $databaseProvider, $highlightService;
            
            return $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier")
                ->getMappedResultSet(function ($placeIdentifierRow) use (&$highlightService) { 
                    return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                        $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
                });
        }

        public function getPlaceIdentifiersByCoordinates($latitude, $longitude) : array {
            global $databaseProvider, $highlightService;
            
            return $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE latitude = ? AND longitude = ?")
                ->withParameters($latitude, $longitude)
                ->getMappedResultSet(function ($placeIdentifierRow) use (&$highlightService) { 
                    return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                        $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
                });
        }

        public function getPlaceIdentifiersByCountry($country) : array {
            global $databaseProvider, $highlightService;
            
            return $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE country = ?")
                ->withParameters($country)
                ->getMappedResultSet(function ($placeIdentifierRow) use (&$highlightService) { 
                    return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                        $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
                });
        }

        public function getPlaceIdentifiersByCategoryId($categoryId) : array {
            global $databaseProvider, $highlightService;
            
            return $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id IN (SELECT place_id FROM category WHERE category_id = ?)")
                ->withParameters($categoryId)
                ->getMappedResultSet(function ($placeIdentifierRow) use (&$highlightService) { 
                    return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                        $placeIdentifierRow["timezone"], $highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
                });
        }

        public function getOrCreatePlaceIdentifier($name, $country, $address) : PlaceIdentifier {            
            global $databaseProvider, $configuration, $schedulingProvider, $geocodingService;

            $placeIdentifier = $this->getPlaceIdentifier($name, $country);
            if ($placeIdentifier !== NULL) {
                return $placeIdentifier;
            }

            if ($country == $configuration["countryNames"]["UNKNOWN"]) {
                throw new InvalidArgumentException("Cannot create an identifier for an unknown country.");
            }
            
            $location = $geocodingService->getLocation($address);

            $databaseProvider
                ->statementBuilder("INSERT INTO place_identifier (name, country, timezone, latitude, longitude, excerpt) VALUES (?, ?, ?, ?, ?, ?)")
                ->withParameters($name, $country, $location->getTimezone(), $location->getLatitude(), $location->getLongitude(), $this->getSuggestedExcerpt($name, $country))
                ->execute();

            $placeIdentifier = $this->getPlaceIdentifier($name, $country);
                
            $schedulingProvider
                ->scheduleJobExecution("UpdateCategories", array(
                    "placeId" => $placeIdentifier->getId()), NULL);

            return $placeIdentifier;
        }

        public function movePlaces($tripId, $offset) : array {
            global $configuration, $googleApiClient;

            $places = $this->getRegularPlaces(NULL, $tripId, NULL, NULL, NULL, NULL, TRUE, TRUE, TRUE);

            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $timezoneOffset = $this->getTimezoneOffset($date->getStart(), $configuration["homeLocation"]["timezone"], $place->getTimezone());
                    $googleApiClient->updateCalendarEventDates("places", $this->getPlaceEventId($place->getId(), $date->getStart()), $date->getStart() + $timezoneOffset + $offset, $date->getEnd() + $timezoneOffset + $offset);
                }
            }

            return $places;
        }

        public function loadPlaces($candidateTripId, $startOffset) : array {
            global $googleApiClient;

            $places = $this->doGetCandidatePlacesForTrip(NULL, $candidateTripId, TRUE, TRUE, TRUE);

            foreach ($places as &$place) {
                $address = $place->getName() . ", " . $place->getCountry() . " (" . $place->getLatitude() . ", " . $place->getLongitude() . ")";

                foreach ($place->getDates() as &$date) {
                    $googleApiClient->createCalendarEvent("places", $place->getName(), $address, $startOffset + $date->getStart(), $startOffset + $date->getEnd());
                }
            }

            return $places;
        }

        public function archivePlaces($tripId, $tripStart, $archivedTripId) : array {
            global $configuration, $databaseProvider, $googleApiClient;

            $places = $this->getRegularPlaces(NULL, $tripId, NULL, NULL, NULL, NULL, FALSE, FALSE, FALSE);
            
            foreach ($places as &$place) {
                foreach ($place->getDates() as &$date) {
                    $timeOffset = $this->getTimezoneOffset($date->getStart(), $configuration["homeLocation"]["timezone"], $place->getTimezone());
                    $databaseProvider
                        ->statementBuilder("INSERT INTO place_candidate_event (place_id, trip_id, start, end) VALUES (?, ?, ?, ?)")
                        ->withParameters($place->getId(), $archivedTripId, $date->getStart() - $timeOffset - $tripStart, $date->getEnd() - $timeOffset - $tripStart)
                        ->execute();
                    $googleApiClient->deleteCalendarEvent("places", $this->getPlaceEventId($place->getId(), $date->getStart()));
                }
            }
            
            return $this->doGetCandidatePlacesForTrip(NULL, $archivedTripId, TRUE, TRUE, TRUE);
        }

        public function createPermanentPlace($name, $address) : Place {
            return $this->createSpecialPlace(SpecialPlaceType::Permanent, $name, $address);
        }

        public function createCandidatePlace($name, $address) : Place {
            return $this->createSpecialPlace(SpecialPlaceType::Candidate, $name, $address);
        }

        private function createSpecialPlace($specialPlaceType, $name, $address) : Place {            
            global $databaseProvider, $configurationService, $geocodingService;

            $country = $geocodingService->getLocation($address)->getCountry();

            $placeIdentifier = $this->getOrCreatePlaceIdentifier($name, $country, $address);

            // TODO: Remove the create-if-not-exists semantics.
            $databaseProvider
                ->statementBuilder("DELETE FROM " . $specialPlaceType->getTableName() . " WHERE place_id = ?")
                ->withParameters($placeIdentifier->getId())
                ->execute();

            $databaseProvider
                ->statementBuilder("INSERT INTO " . $specialPlaceType->getTableName() . " (place_id) VALUES (?)")
                ->withParameters($placeIdentifier->getId())
                ->execute();

            $configurationService->updateConfigurationEntryVisibility(array("public", "modifiable"), "COUNTRIES", $placeIdentifier->getCountry());
    
            return new Place($placeIdentifier->getId(), $placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getLatitude(),
                $placeIdentifier->getLongitude(), $placeIdentifier->getTimezone(), $placeIdentifier->getMainHighlight(), $placeIdentifier->getExcerpt(), array(), array(), array());
        }

        public function removePermanentPlace($placeId) : bool {
            global $databaseProvider, $schedulingProvider;

            $wasRemoved = $this->removeSpecialPlace(SpecialPlaceType::Permanent, $placeId);

            $categoryIdsToUpdate = $databaseProvider
                ->statementBuilder("SELECT category_id FROM category WHERE place_id = ?")
                ->withParameters($placeId)
                ->getResultSetForColumn("category_id");

            foreach ($categoryIdsToUpdate as &$categoryIdToUpdate) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => StatisticsType::Category->value, 
                        "id" => $categoryIdToUpdate), NULL);
            }

            $yearsToUpdate = $databaseProvider
                ->statementBuilder("SELECT DISTINCT YEAR(FROM_UNIXTIME(start)) AS year FROM place_summary WHERE place_id = ?")
                ->withParameters($placeId)
                ->getResultSetForColumn("year");

            foreach ($yearsToUpdate as &$yearToUpdate) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => StatisticsType::Year->value, 
                        "id" => $yearToUpdate), NULL);
            }

            return $wasRemoved;
        }

        public function removeCandidatePlace($placeId) : bool {
            return $this->removeSpecialPlace(SpecialPlaceType::Candidate, $placeId);
        }

        private function removeSpecialPlace($specialPlaceType, $placeId) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("DELETE FROM " . $specialPlaceType->getTableName() . " WHERE place_id = ?")
                ->withParameters($placeId)
                ->execute() === 1;
        }

        private function getSuggestedExcerpt($name, $country) : ?string {
            global $configuration, $chatClient;

            return $chatClient->getResponse(sprintf($configuration["chatRequests"]["suggestedExcerpt"], $name, $country));
        }

        private function getPlaceEventId($placeId, $start) : ?string {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT id FROM place_event WHERE place_id = ? AND start = ?")
                ->withParameters($placeId, $start)
                ->getSingleColumn("id");
        }

        private function getContainedTripIdentifiers($placeId) : array {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT DISTINCT trip_id FROM place_summary WHERE place_id = ? AND trip_id IS NOT NULL")
                ->withParameters($placeId)
                ->getResultSetForColumn("trip_id");
        }

        private function getTimezoneOffset($timestamp, $fromTimezone, $toTimezone) {
            $timezone = new DateTimeZone($fromTimezone);
            $dateTimeHome = new DateTime(date('m/d/Y H:i:s', $timestamp), new DateTimeZone($toTimezone));
            return $timezone->getOffset($dateTimeHome) - (new DateTimeZone($toTimezone))->getOffset($dateTimeHome);
        }
    }

    enum SpecialPlaceType {
        case Candidate;
        case Permanent;

        public function getTableName() : string {
            return match ($this) {
                self::Candidate => "place_candidate",
                self::Permanent => "place_permanent"
            };
        }
    }
?>