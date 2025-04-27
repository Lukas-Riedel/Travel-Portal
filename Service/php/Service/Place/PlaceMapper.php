<?php
    namespace Service\Service\Place;

    use Service\Service\Geocoding\GeocodingService;
    use Service\Service\Category\CategoryService;
    use Service\Service\Forecast\ForecastService;
    use Service\Service\Highlight\HighlightService;
    use Service\Service\Label\LabelService;
    use Service\Service\Photo\PhotoService;
    use Service\Service\Statistics\KeyValuePair;

    class PlaceMapper {

        private readonly \DatabaseProvider $databaseProvider;

        private readonly \ConfigurationService $configurationService;

        private readonly CategoryService $categoryService;
        private readonly LabelService $labelService;
        private readonly ForecastService $forecastService;
        private readonly PhotoService $photoService;
        private readonly HighlightService $highlightService;
        
        private readonly GeocodingService $geocodingService;

        private array $countries = array();

        public function __construct(\DatabaseProvider $databaseProvider, \ConfigurationService $configurationService, CategoryService $categoryService, LabelService $labelService,
            ForecastService $forecastService, PhotoService $photoService, HighlightService $highlightService, GeocodingService $geocodingService) {
            $this->databaseProvider = $databaseProvider;
            $this->geocodingService = $geocodingService;
            $this->categoryService = $categoryService;
            $this->labelService = $labelService;
            $this->forecastService = $forecastService;
            $this->photoService = $photoService;
            $this->highlightService = $highlightService;
            $this->configurationService = $configurationService;
        }

        public function selectDatesForTripAndCountry(string $tripId, string $country) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT DATE_FORMAT(FROM_UNIXTIME(start), '%e.%c.%Y') AS date
                FROM place_summary
                WHERE trip_id = ?
                    AND country = ?
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId, $country)
                ->getResultSetForColumn("date");
        }

        public function selectCountriesForTrip(string $tripId) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT country
                FROM place_summary
                WHERE trip_id = ?
                    AND NOT layover
                GROUP BY country
                ORDER BY MIN(start)
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getResultSetForColumn("country");
        }

        public function selectCountriesForCandidateTrip(string $tripId) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT pi.country_category_id
                FROM place_candidate_event pce
                INNER JOIN place_identifier pi
                    ON pce.place_id = pi.id
                WHERE pce.trip_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSetForColumn("country_category_id", function($categoryId) {
                    return $this->categoryService->getCategoryIdentifierById($categoryId)->getName();
                });
        }

        public function selectVisitedCountriesCount(int $start, int $end) : int {
            $sql = <<<'SQL'
                SELECT COUNT(DISTINCT pi.country_category_id) AS countries_count
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.start >= ?
                    AND pe.end <= ?
            SQL;

            return intval($this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getSingleColumn("countries_count"));
        }
        public function selectVisitedPlacesCount(int $start, int $end, ?string $categoryId) : int {
            $sql = <<<'SQL'
                SELECT COUNT(DISTINCT place_id) AS places_count
                FROM place_event
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()
                ->withClause("start >= ?", $start)
                ->withClause("end <= ?", $end);
            
            if ($categoryId !== NULL) {
                $allowedPlaceIds = $this->categoryService->getPlaceIdsForCategory($categoryId);
                if (count($allowedPlaceIds) === 0) {
                    return 0;
                }
                $whereClauseBuilder->withClause("place_id IN (" . implode(", ", array_fill(0, count($allowedPlaceIds), "?")) . ")", ...$allowedPlaceIds);
            }
            
            $whereClause = $whereClauseBuilder->buildForAnd();

            return intval($this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getSingleColumn("places_count"));
        }

        public function selectLastVisit(int $start, int $end, ?string $categoryId) : int {
            $sql = <<<'SQL'
                SELECT MAX(end) AS last_visit
                FROM place_event
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()
                ->withClause("start >= ?", $start)
                ->withClause("end <= ?", $end);
            
            if ($categoryId !== NULL) {
                $allowedPlaceIds = $this->categoryService->getPlaceIdsForCategory($categoryId);
                if (count($allowedPlaceIds) === 0) {
                    return 0;
                }
                $whereClauseBuilder->withClause("place_id IN (" . implode(", ", array_fill(0, count($allowedPlaceIds), "?")) . ")", ...$allowedPlaceIds);
            }
            
            $whereClause = $whereClauseBuilder->buildForAnd();

            return ($this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getSingleColumn("last_visit"));
        }

        public function selectVisitedPlacesCountByCountry(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT pi.country_category_id,
                    COUNT(DISTINCT pi.name) AS places_count
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.start >= ?
                    AND pe.end <= ?
                GROUP BY pi.country_category_id
                ORDER BY COUNT(DISTINCT name) DESC
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getMappedResultSet(function($placeIdentifierRow) {
                    return new KeyValuePair($this->categoryService->getCategoryIdentifierById($placeIdentifierRow["country_category_id"])->getName(),
                        $placeIdentifierRow["places_count"]);
                });            
        }

        public function selectTotalTravelDaysCountByCountry(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT pi.country_category_id,
                    COUNT(DISTINCT ROUND(start / 86400)) AS travel_days_count
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.start >= ?
                    AND pe.end <= ?
                    AND pi.country_category_id <> ?
                GROUP BY pi.country_category_id
                ORDER BY COUNT(DISTINCT FLOOR(start / 86400)) DESC
            SQL;

            $homeCountryCategoryId = $this->categoryService->getOrCreateCountryCategoryIdentifier(
                $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "country"))->getId();

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end, $homeCountryCategoryId)
                ->getMappedResultSet(function($placeIdentifierRow) {
                    return new KeyValuePair($this->categoryService->getCategoryIdentifierById($placeIdentifierRow["country_category_id"])->getName(),
                        $placeIdentifierRow["travel_days_count"]);
                });            
        }

        public function selectVisitedPlacesCountByCategory(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT pi.*
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.start >= ?
                    AND pe.end <= ?
            SQL;

            $placeIdentifierRows = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getResultSet();

            $visits = array();
            foreach ($placeIdentifierRows as &$placeIdentifierRow) {
                $placeCategoryIds = $this->categoryService->getCategoryIdsForPlace($placeIdentifierRow["id"]);
                foreach ($placeCategoryIds as &$placeCategoryId) {
                    if (!isset($visits[$placeCategoryId])) {
                        $visits[$placeCategoryId] = 1;
                    }
                    else {
                        ++$visits[$placeCategoryId];
                    }
                }
            }
            arsort($visits);

            return array_map(fn($categoryId) => new KeyValuePair($this->categoryService->getCategoryIdentifierById($categoryId)->getName(),
                $visits[$categoryId]), array_keys($visits));          
        }

        public function selectWesternmostPlaces(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT pi.*
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.start >= ?
                    AND pe.end <= ?
                ORDER BY longitude ASC
            SQL;
            
            $homeLatitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "latitude");
            $homeLongitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "longitude");

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getMappedResultSet(function($placeIdentifierRow) use($homeLatitude, $homeLongitude) {
                    $distance = $this->geocodingService->getDistance($homeLatitude, $homeLongitude, $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"]);
                    return new KeyValuePair($placeIdentifierRow["name"], intval($distance));
                });
        }

        public function selectEasternmostPlaces(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT pi.*
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.start >= ?
                    AND pe.end <= ?
                ORDER BY longitude DESC
            SQL;
            
            $homeLatitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "latitude");
            $homeLongitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "longitude");

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getMappedResultSet(function($placeIdentifierRow) use($homeLatitude, $homeLongitude) {
                    $distance = $this->geocodingService->getDistance($homeLatitude, $homeLongitude, $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"]);
                    return new KeyValuePair($placeIdentifierRow["name"], intval($distance));
                });
        }

        public function selectNorthernmostPlaces(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT pi.*
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.start >= ?
                    AND pe.end <= ?
                ORDER BY latitude DESC
            SQL;
            
            $homeLatitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "latitude");
            $homeLongitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "longitude");

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getMappedResultSet(function($placeIdentifierRow) use($homeLatitude, $homeLongitude) {
                    $distance = $this->geocodingService->getDistance($homeLatitude, $homeLongitude, $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"]);
                    return new KeyValuePair($placeIdentifierRow["name"], intval($distance));
                });
        }

        public function selectSouthernmostPlaces(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT pi.*
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.start >= ?
                    AND pe.end <= ?
                ORDER BY latitude ASC
            SQL;
            
            $homeLatitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "latitude");
            $homeLongitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "longitude");

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getMappedResultSet(function($placeIdentifierRow) use($homeLatitude, $homeLongitude) {
                    $distance = $this->geocodingService->getDistance($homeLatitude, $homeLongitude, $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"]);
                    return new KeyValuePair($placeIdentifierRow["name"], intval($distance));
                });
        }

        public function selectLeastRecentlyVisitedPlaces(int $start, int $end, ?string $categoryId) : array {
            $sql = <<<'SQL'
                SELECT name,
                    last_visit
                FROM (
                    SELECT pi.*,
                        pe.start,
                        pe.end,
                        MAX(pe.end) AS last_visit
                    FROM place_event pe
                    INNER JOIN place_identifier pi
                        ON pe.place_id = pi.id
                    WHERE pe.end <= UNIX_TIMESTAMP()
                    GROUP BY pi.id) x
                WHERE :CONDITIONS
                ORDER BY last_visit ASC
            SQL;
            
            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()
                ->withClause("start >= ?", $start)
                ->withClause("end <= ?", $end);
            
            if ($categoryId !== NULL) {
                $allowedPlaceIds = $this->categoryService->getPlaceIdsForCategory($categoryId);
                if (count($allowedPlaceIds) === 0) {
                    return array();
                }
                $whereClauseBuilder->withClause("id IN (" . implode(", ", array_fill(0, count($allowedPlaceIds), "?")) . ")", ...$allowedPlaceIds);
            }

            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($placeIdentifierRow) {
                    return new KeyValuePair($placeIdentifierRow["name"], $placeIdentifierRow["last_visit"]);
                });
        }

        public function selectMostVisitedPlaces(int $start, int $end, ?string $categoryId) : array {
            $sql = <<<'SQL'
                SELECT pi.name,
                    COUNT(DISTINCT pe.trip_id) AS visits_count
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE :CONDITIONS
                GROUP BY pi.id
                HAVING COUNT(DISTINCT pe.trip_id) > 1
                ORDER BY COUNT(DISTINCT pe.trip_id) DESC, MAX(pe.start) ASC
            SQL;
            
            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()
                ->withClause("pe.start >= ?", $start)
                ->withClause("pe.end <= ?", $end);
            
            if ($categoryId !== NULL) {
                $allowedPlaceIds = $this->categoryService->getPlaceIdsForCategory($categoryId);
                if (count($allowedPlaceIds) === 0) {
                    return array();
                }
                $whereClauseBuilder->withClause("pi.id IN (" . implode(", ", array_fill(0, count($allowedPlaceIds), "?")) . ")", ...$allowedPlaceIds);
            }

            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($placeIdentifierRow) {
                    return new KeyValuePair($placeIdentifierRow["name"], $placeIdentifierRow["visits_count"]);
                });
        }

        public function selectTotalTravelDaysCount(int $start, int $end, ?string $categoryId) : int {
            $sql = <<<'SQL'
                SELECT COUNT(*) AS days_count
                FROM (
                    SELECT *
                    FROM place_event
                    WHERE :CONDITIONS
                    GROUP BY FLOOR(start / 86400)
                ) x
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()
                ->withClause("start >= ?", $start)
                ->withClause("end <= ?", $end);
            
            if ($categoryId !== NULL) {
                $allowedPlaceIds = $this->categoryService->getPlaceIdsForCategory($categoryId);
                if (count($allowedPlaceIds) === 0) {
                    return 0;
                }
                $whereClauseBuilder->withClause("place_id IN (" . implode(", ", array_fill(0, count($allowedPlaceIds), "?")) . ")", ...$allowedPlaceIds);
            }
            
            $whereClause = $whereClauseBuilder->buildForAnd();

            return intval($this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getSingleColumn("days_count"));
        }

        public function selectFurthestPlaces(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT pi.*
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.start >= ?
                    AND pe.end <= ?
            SQL;
            
            $homeLatitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "latitude");
            $homeLongitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "longitude");

            $placeIdentifierRows = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getResultSet();

            $distances = array();
            foreach ($placeIdentifierRows as &$placeIdentifierRow) {
                $distance = $this->geocodingService->getDistance($homeLatitude, $homeLongitude, $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"]);
                if (!isset($distances[$placeIdentifierRow["name"]]) || $distances[$placeIdentifierRow["name"]] < $distance) {
                    $distances[$placeIdentifierRow["name"]] = $distance;
                }
            }
            arsort($distances);

            return array_map(fn($name) => new KeyValuePair($name, intval($distances[$name])), array_keys($distances));
        }

        public function selectFurthestCountries(int $start, int $end) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT pi.*
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.start >= ?
                    AND pe.end <= ?
            SQL;
            
            $homeLatitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "latitude");
            $homeLongitude = $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "longitude");

            $placeIdentifierRows = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getResultSet();

            $distances = array();
            foreach ($placeIdentifierRows as &$placeIdentifierRow) {
                $distance = $this->geocodingService->getDistance($homeLatitude, $homeLongitude, $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"]);
                if (!isset($distances[$placeIdentifierRow["country_category_id"]]) || $distances[$placeIdentifierRow["country_category_id"]] > $distance) {
                    $distances[$placeIdentifierRow["country_category_id"]] = $distance;
                }
            }
            arsort($distances);

            return array_map(fn($countryCategoryId) => new KeyValuePair($this->categoryService->getCategoryIdentifierById($countryCategoryId)->getName(),
            intval($distances[$countryCategoryId])), array_keys($distances));
        }

        public function selectDaysForCandidateTrip(string $tripId) : int {
            $sql = <<<'SQL'
                SELECT COALESCE(CEIL(MAX(end) / 86400), 0) AS days
                FROM place_candidate_event
                WHERE trip_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getSingleColumn("days");
        }
        
        public function selectRegularPlaces(?string $placeId, ?string $categoryId, ?string $label, ?string $tripId, ?int $year, ?string $albumId, ?int $minStart, ?int $maxEnd, array $includedEntities) : array {
            // TODO: Introduce a property for TripService $tripService.
            global $tripService;

            $sql = <<<'SQL'
                SELECT *
                FROM place_summary
                WHERE :CONDITIONS
                ORDER BY start
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder();
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
            if ($label !== NULL) {
                $whereClauseBuilder->withClause("FIND_IN_SET(?, label_names)", $label);
            }
            if ($minStart !== NULL) {
                $whereClauseBuilder->withClause("? <= start", $minStart);
            }
            if ($maxEnd !== NULL) {
                $whereClauseBuilder->withClause("end <= ?", $maxEnd);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            $placeRows = $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getResultSet();

            $places = array();
            foreach ($placeRows as &$placeRow) {
                if (!isset($places[$placeRow["place_id"]])) {
                    $categories = array();
                    if (in_array(PlaceIncludedEntity::Categories->value, $includedEntities)) {
                        $categories = $this->categoryService->getCategoryIdentifiersByIds(explode(",", $placeRow["category_ids"]));
                    }                   

                    $highlights = array();         
                    if (in_array(PlaceIncludedEntity::Highlights->value, $includedEntities)) {
                        $highlights = $this->highlightService->getPlaceHighlights($placeRow["place_id"]);                      
                    }                 

                    $labels = array();         
                    if (in_array(PlaceIncludedEntity::Labels->value, $includedEntities)) {
                        $labels = $this->labelService->getLabelsForPlace($placeRow["place_id"]);                      
                    }
                    
                    $excerpt = NULL;
                    if (in_array(PlaceIncludedEntity::Excerpt->value, $includedEntities)) {
                        $excerpt = $placeRow["excerpt"];
                    }
                    
                    $places[$placeRow["place_id"]] = new Place($placeRow["place_id"], $placeRow["name"], $placeRow["country"], $placeRow["latitude"], $placeRow["longitude"], $placeRow["timezone"],
                        $this->highlightService->getHighlight($placeRow["main_highlight_id"]), $excerpt, $categories, $highlights, $labels, array());
                }
                
                if (in_array(PlaceIncludedEntity::Dates->value, $includedEntities)) {
                    $weather = NULL;
                    $sun = NULL;
                    if ($placeRow["end"] > time()) {
                        $weather = $this->forecastService->getWeatherForecast($placeRow["place_id"], $placeRow["start"]);
                        $sun = $this->forecastService->getDaylightForecast($placeRow["place_id"], $placeRow["start"]);
                    }
    
                    $album = NULL;
                    if ($placeRow["album_id"] !== NULL) {
                        $album = $this->photoService->getAlbum($placeRow["album_id"]);    
                    }
    
                    $trip = NULL;
                    if ($placeRow["trip_id"] !== NULL) {
                        $trip = $tripService->getTripIdentifierById($placeRow["trip_id"]);
                    }
    
                    $places[$placeRow["place_id"]]->addDate(new Date($placeRow["start"], $placeRow["end"], $placeRow["layover"] == 1, $weather, $sun, $album, $trip));  
                }
            }

            // Process permananent places without albums.
            $sql = <<<'SQL'
                SELECT pi.*
                FROM place_permanent pp
                INNER JOIN place_identifier pi
                    ON pp.place_id = pi.id
                WHERE :CONDITIONS
            SQL;
            
            if ($tripId === NULL && $albumId === NULL) {
                $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder();
                if ($placeId !== NULL) {
                    $whereClauseBuilder->withClause("pi.id = ?", $placeId);
                }
                $whereClause = $whereClauseBuilder->withClause("pi.id NOT IN (SELECT place_id FROM place_summary)")->buildForAnd();

                $placeRows = $this->databaseProvider
                    ->statementBuilder($sql, $whereClause)
                    ->getResultSet();

                foreach ($placeRows as &$placeRow) {
                    if ($categoryId !== NULL && !in_array($categoryId, $this->categoryService->getCategoryIdsForPlace($placeRow["id"]))) {
                        continue;
                    }

                    if (!isset($places[$placeRow["id"]])) {
                        $categories = array();
                        if (in_array(PlaceIncludedEntity::Categories->value, $includedEntities)) {
                            $categories = $this->categoryService->getCategoryIdentifiersForPlace($placeRow["id"]);
                        }                   

                        $highlights = array();         
                        if (in_array(PlaceIncludedEntity::Highlights->value, $includedEntities)) {
                            $highlights = $this->highlightService->getPlaceHighlights($placeRow["id"]);                      
                        }         

                        $labels = array();         
                        if (in_array(PlaceIncludedEntity::Labels->value, $includedEntities)) {
                            $labels = $this->labelService->getLabelsForPlace($placeRow["id"]);                      
                        }
                        
                        $excerpt = NULL;
                        if (in_array(PlaceIncludedEntity::Excerpt->value, $includedEntities)) {
                            $excerpt = $placeRow["excerpt"];
                        }
                        
                        $places[$placeRow["id"]] = new Place($placeRow["id"], $placeRow["name"], $this->selectCountry($placeRow["country_category_id"]), $placeRow["latitude"],
                            $placeRow["longitude"], $placeRow["timezone"], $this->highlightService->getHighlight($placeRow["main_highlight_id"]), $excerpt, $categories, $highlights, $labels, array());
                    }
                }
            }

            return array_values($places);
        }
        
        public function selectCandidatePlaces(?string $placeId, ?string $categoryId, array $includedEntities) : array {
            $sql = <<<'SQL'
                SELECT pi.*
                FROM place_candidate pc
                INNER JOIN place_identifier pi
                    ON pc.place_id = pi.id
                WHERE :CONDITIONS
                ORDER BY name
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder(); 
            if ($placeId !== NULL) {
                $whereClauseBuilder->withClause("pi.id = ?", $placeId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $placeRows = $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getResultSet();

            $places = array();
            foreach ($placeRows as &$placeRow) {   
                if ($categoryId !== NULL && !in_array($categoryId, $this->categoryService->getCategoryIdsForPlace($placeRow["id"]))) {
                    continue;
                }
                             
                $highlights = array();
                if (in_array(PlaceIncludedEntity::Highlights->value, $includedEntities)) {
                    $highlights = $this->highlightService->getPlaceHighlights($placeRow["id"]);                      
                }
                
                $excerpt = NULL;
                if (in_array(PlaceIncludedEntity::Excerpt->value, $includedEntities)) {
                    $excerpt = $placeRow["excerpt"];
                }         

                $labels = array();         
                if (in_array(PlaceIncludedEntity::Labels->value, $includedEntities)) {
                    $labels = $this->labelService->getLabelsForPlace($placeRow["id"]);                      
                }

                $categories = array();
                if (in_array(PlaceIncludedEntity::Categories->value, $includedEntities)) {
                    $categories = $this->categoryService->getCategoryIdentifiersForPlace($placeRow["id"]);
                }

                $places[] = new Place($placeRow["id"], $placeRow["name"], $this->selectCountry($placeRow["country_category_id"]), $placeRow["latitude"],
                    $placeRow["longitude"], $placeRow["timezone"], NULL, $excerpt, $categories, $highlights, $labels, array());
            }
            
            return $places;
        }
        
        public function selectCandidatePlacesForTrip(?string $categoryId, string $tripId, array $includedEntities) {         
            // TODO: Introduce a property instead.
            global $tripService;

            $sql = <<<'SQL'
                SELECT pi.*,
                    pce.start,
                    pce.end
                FROM place_candidate_event pce
                INNER JOIN place_identifier pi
                    ON pce.place_id = pi.id
                WHERE trip_id = ?
            SQL;

            $placeRows = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getResultSet();
                
            $trip = $tripService->getTripIdentifierById($tripId);

            $places = array();            
            foreach ($placeRows as &$placeRow) {
                if ($categoryId !== NULL && !in_array($categoryId, $this->categoryService->getCategoryIdsForPlace($placeRow["id"]))) {
                    continue;
                }

                if (!isset($places[$placeRow["id"]])) {
                    $highlights = array();
                    if (in_array(PlaceIncludedEntity::Highlights->value, $includedEntities)) {
                        $highlights = $this->highlightService->getPlaceHighlights($placeRow["id"]);                      
                    }
                    
                    $excerpt = NULL;
                    if (in_array(PlaceIncludedEntity::Excerpt->value, $includedEntities)) {
                        $excerpt = $placeRow["excerpt"];
                    }         

                    $labels = array();         
                    if (in_array(PlaceIncludedEntity::Labels->value, $includedEntities)) {
                        $labels = $this->labelService->getLabelsForPlace($placeRow["id"]);                      
                    }

                    $categories = array();
                    if (in_array(PlaceIncludedEntity::Categories->value, $includedEntities)) {
                        $categories = $this->categoryService->getCategoryIdentifiersForPlace($placeRow["id"]);
                    }

                    $places[$placeRow["id"]] = new Place($placeRow["id"], $placeRow["name"], $this->selectCountry($placeRow["country_category_id"]), $placeRow["latitude"],
                        $placeRow["longitude"], $placeRow["timezone"], NULL, $excerpt, $categories, $highlights, $labels, array()); 
                }
                
                if (in_array(PlaceIncludedEntity::Dates->value, $includedEntities)) {
                    $places[$placeRow["id"]]->addDate(new Date($placeRow["start"], $placeRow["end"], FALSE, NULL, NULL, NULL, $trip));
                }
            }

            return array_values($places);
        }

        public function selectPlaceIdentifier(string $name, string $country) : ?PlaceIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM place_identifier
                WHERE name = ?
                    AND country_category_id = ?
            SQL;

            $placeIdentifierRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($name, $this->categoryService->getCategoryIdentifier($country)->getId())
                ->getSingleRow();

            if ($placeIdentifierRow === NULL) {
                return NULL;
            }

            return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $this->selectCountry($placeIdentifierRow["country_category_id"]), $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                $placeIdentifierRow["timezone"], $this->highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
        }

        public function selectPlaceIdentifierById(string $placeId) : ?PlaceIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM place_identifier
                WHERE id = ?
            SQL;

            $placeIdentifierRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeId)
                ->getSingleRow();

            if ($placeIdentifierRow === NULL) {
                return NULL;
            }

            return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $this->selectCountry($placeIdentifierRow["country_category_id"]), $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                $placeIdentifierRow["timezone"], $this->highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["excerpt"]);
        }

        public function selectPlaceEventId(string $placeId, int $start) : ?string {
            $sql = <<<'SQL'
                SELECT id
                FROM place_event
                WHERE place_id = ?
                    AND start = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeId, $start)
                ->getSingleColumn("id");
        }

        public function selectPlaceIdsForCreatedPlaceEvents(string $oldPlaceEventTableName) : array {
            $sql = <<<SQL
                SELECT npe.place_id
                FROM place_event npe
                LEFT JOIN {$oldPlaceEventTableName} ope
                    ON ope.id = npe.id
                WHERE ope.start IS NULL
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("place_id");
        }

        public function selectPlaceIdsForUpdatedPlaceEvents(string $oldPlaceEventTableName) : array {
            $sql = <<<SQL
                SELECT npe.place_id
                FROM place_event npe
                INNER JOIN {$oldPlaceEventTableName} ope
                    ON ope.id = npe.id
                WHERE ope.start <> npe.start
                    OR ope.end <> npe.end
                    OR ope.layover <> npe.layover
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("place_id");
        }

        public function selectPlaceIdsForDeletedPlaceEvents(string $oldPlaceEventTableName) : array {
            $sql = <<<SQL
                SELECT ope.place_id
                FROM {$oldPlaceEventTableName} ope
                LEFT JOIN place_event npe
                    ON ope.id = npe.id
                WHERE npe.id IS NULL
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("place_id");
        }

        public function updatePlaceMainHighlight(string $placeId, string $highlightIdentifier) : bool {
            $sql = <<<'SQL'
                UPDATE place_identifier
                SET main_highlight_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($highlightIdentifier, $placeId)
                ->execute() === 1;
        }

        public function updatePlaceExcerpt(string $placeId, string $excerpt) : bool {
            $sql = <<<'SQL'
                UPDATE place_identifier
                SET excerpt = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($excerpt, $placeId)
                ->execute() === 1;
        }

        public function updatePlaceName(string $placeId, string $name) : bool {
            $sql = <<<'SQL'
                UPDATE place_identifier
                SET name = ?,
                    excerpt = NULL
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($name, $placeId)
                ->execute() === 1;
        }

        public function insertPlaceIdentifier(PlaceIdentifier $placeIdentifier) : bool {
            $sql = <<<'SQL'
                INSERT INTO place_identifier (
                    name, 
                    country_category_id, 
                    timezone, 
                    latitude, 
                    longitude, 
                    excerpt
                )
                VALUES (
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?
                )
            SQL;
            
            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeIdentifier->getName(), $placeIdentifier->getCountry(), $placeIdentifier->getTimezone(),
                    $placeIdentifier->getLatitude(), $placeIdentifier->getLongitude(), $placeIdentifier->getExcerpt())
                ->execute() === 1;
                
            if ($wasInserted) {
                $placeIdentifier->setId($this->databaseProvider->getLastInsertedId());
            }

            return $wasInserted;
        }

        public function insertPlaceCandidateEvent(Place $place) : bool {
            $sql = <<<'SQL'
                INSERT INTO place_candidate_event (
                    place_id,
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

            $wasInserted = count($place->getDates()) > 0;
            foreach ($place->getDates() as &$date) {                
                $wasInserted &= $this->databaseProvider
                    ->statementBuilder($sql)
                    ->withParameters($place->getId(), $date->getTrip()->getId(), $date->getStart(), $date->getEnd())
                    ->execute() === 1;
            }

            return $wasInserted;
        }

        public function insertPlaceEvent(Place $place, string $eventId) : bool {
            $sql = <<<'SQL'
                INSERT INTO place_event (
                    id,
                    place_id,
                    trip_id,
                    start,
                    end,
                    layover
                )
                VALUES (
                    ?, 
                    ?, 
                    ?, 
                    ?,
                    ?,
                    ?
                )
            SQL;

            $wasInserted = count($place->getDates()) > 0;
            foreach ($place->getDates() as &$date) {                
                $wasInserted &= $this->databaseProvider
                    ->statementBuilder($sql)
                    ->withParameters($eventId, $place->getId(), $date->getTrip()->getId(), $date->getStart(), $date->getEnd(), $date->isLayover() ? 1 : 0)
                    ->execute() === 1;
            }

            return $wasInserted;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeEvent->getId(), $placeIdentifier->getId(), $resolvedTripIdentifier->getId(), $start, $end, $isLayover ? 1 : 0)
                ->execute();
        }

        public function insertSpecialPlace(SpecialPlaceType $specialPlaceType, string $placeId) : bool {
            $sql = <<<SQL
                INSERT INTO {$specialPlaceType->getTableName()} (
                    place_id
                )
                VALUES (
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeId)
                ->execute();
        }

        public function updatePlaceLocation(string $placeId, float $latitude, float $longitude) : bool {
            $sql = <<<'SQL'
                UPDATE place_identifier
                SET latitude = ?,
                    longitude = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($latitude, $longitude, $placeId)
                ->execute() === 1;
        }

        public function deleteCandidateEventsForCandidateTrip(string $tripId) : int {
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

        public function deleteAllPlaceEvents() : int {
            $sql = <<<'SQL'
                DELETE
                FROM place_event
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }

        public function deleteSpecialPlace(SpecialPlaceType $specialPlaceType, string $placeId) : int {
            $sql = <<<SQL
                DELETE
                FROM {$specialPlaceType->getTableName()}
                WHERE place_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeId)
                ->execute();
        }

        public function createPlaceEventTemporaryTable(string $tableName) : void {            
            $sql = <<<SQL
                DROP TEMPORARY TABLE IF EXISTS {$tableName}
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();    

            $sql = <<<SQL
                CREATE TEMPORARY TABLE {$tableName} AS
                    SELECT p.*,
                        ps.album_id,
                        ps.category_ids
                    FROM place_event p
                    INNER JOIN _place_summary ps
                        ON p.id = ps.id
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }

        private function selectCountry(string $countryCategoryId) : string {
            if (array_key_exists($countryCategoryId, $this->countries)) {
                return $this->countries[$countryCategoryId];
            }

            $country = $this->categoryService->getCategoryIdentifierById($countryCategoryId)->getName();
            $this->countries[$countryCategoryId] = $country;
            return $country;
        }
    }
?>