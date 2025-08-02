<?php
    namespace Service\Service\Place;

    use Service\Service\Category\CategoryCategory;
    use Service\Service\Category\CategoryService;
    use Service\Service\Configuration\ConfigurationService;
    use Service\Service\Forecast\ForecastService;
    use Service\Service\Highlight\HighlightService;
    use Service\Service\Label\LabelService;
    use Service\Service\Note\NoteService;
    use Service\Service\Photo\PhotoService;

    class PlaceMapper {

        private const DMY_DATE_FORMAT = "j.n.Y";
        private const ONE_DAY_SECONDS = 86400;

        private const VISITED_CATEGORIES_TEMPORARY_TABLE_NAME = "visited_categories";

        private readonly \DatabaseProvider $databaseProvider;

        private readonly ConfigurationService $configurationService;

        private readonly CategoryService $categoryService;
        private readonly LabelService $labelService;
        private readonly ForecastService $forecastService;
        private readonly PhotoService $photoService;
        private readonly HighlightService $highlightService;
        private readonly NoteService $noteService;

        private array $countries = array();

        public function __construct(\DatabaseProvider $databaseProvider, ConfigurationService $configurationService, CategoryService $categoryService, LabelService $labelService,
            ForecastService $forecastService, PhotoService $photoService, HighlightService $highlightService, NoteService $noteService) {
            $this->databaseProvider = $databaseProvider;
            $this->categoryService = $categoryService;
            $this->labelService = $labelService;
            $this->forecastService = $forecastService;
            $this->photoService = $photoService;
            $this->highlightService = $highlightService;
            $this->configurationService = $configurationService;
            $this->noteService = $noteService;
        }

        public function selectDatesForTripAndCountry(string $tripId, string $country) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT DATE_FORMAT(FROM_UNIXTIME(pe.start), '%e.%c.%Y') AS date
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE pe.trip_id = ?
                    AND pi.country_category_id = ?
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId, $this->categoryService->getCategoryIdentifier($country)->getId())
                ->getResultSetForColumn("date");
        }

        public function selectCountriesForTrip(string $tripId) : array {
            $sql = <<<'SQL'
                SELECT DISTINCT pi.country_category_id
                FROM place_event pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE trip_id = ?
                    AND NOT layover
                GROUP BY pi.country_category_id
                ORDER BY MIN(pe.start)
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($tripId)
                ->getMappedResultSetForColumn("country_category_id", function($categoryId) {
                    return $this->categoryService->getCategoryIdentifierById($categoryId)->getName();
                });
        }

        public function selectVisitedCategoriesForInterval(int $start, int $end, ?CategoryCategory $category, VisitedCategoriesSortingStrategy $visitedCategoriesSortingStrategy) : array {
            $temporaryTableName = self::VISITED_CATEGORIES_TEMPORARY_TABLE_NAME;
            
            $sql = <<<SQL
                DROP TEMPORARY TABLE IF EXISTS {$temporaryTableName}
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();

            $sql = <<<SQL
                CREATE TEMPORARY TABLE {$temporaryTableName} (
                    category_id bigint(20) unsigned NOT NULL,
                    place_id bigint(20) unsigned NOT NULL
                )
            SQL;
            
            $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();

            foreach ($this->categoryService->getCategoryIdsForCategory($category) as &$categoryId) {
                foreach ($this->categoryService->getPlaceIdsForCategoryId($categoryId) as &$placeId) {                    
                    $sql = <<<SQL
                        INSERT INTO {$temporaryTableName} (
                            category_id,
                            place_id
                        )
                        VALUES (
                            ?,
                            ?
                        )
                    SQL;
                    
                    $this->databaseProvider
                        ->statementBuilder($sql)
                        ->withParameters($categoryId, $placeId)
                        ->execute();
                }
            }

            $sql = <<<SQL
                SELECT c.category_id, GROUP_CONCAT(DISTINCT c.place_id SEPARATOR ",") AS place_ids
                FROM {$temporaryTableName} c
                INNER JOIN (
                    SELECT place_id, start
                    FROM place_event
                    WHERE start >= ?
                        AND end <= ?
                    UNION ALL
                    SELECT place_id, NULL AS start
                    FROM place_permanent
                ) p
                    ON c.place_id = p.place_id
                GROUP BY c.category_id
                {$visitedCategoriesSortingStrategy->value}
            SQL;
            
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($start, $end)
                ->getMappedResultSet(function($categoryRow) use(&$start, &$end) {
                    return new CategoryPlaces($this->categoryService->getCategoryIdentifierById($categoryRow["category_id"]),
                        array_filter(array_map(function($placeId) use(&$start, &$end) {
                            $places = $this->selectRegularPlaces($placeId, NULL, NULL, NULL, NULL, NULL, NULL, NULL, $start, $end,
                                array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::Default);
                            return count($places) === 0 ? NULL : $places[0];
                        }, explode(",", $categoryRow["place_ids"])), fn($place) => $place !== NULL));
                });
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
        
        public function selectRegularPlaces(?string $placeId, ?string $categoryId, ?string $labelId, ?string $tripId, ?int $year, ?string $albumId, ?string $photoId, ?float $maxQuality, ?int $minStart, ?int $maxEnd, array $includedEntities, PlaceSortingStrategy $placeSortingStrategy) : array {
            // TODO: Introduce a property for TripService $tripService.
            global $tripService;

            $sql = <<<SQL
                SELECT pi.*,
                    pe.trip_id,
                    pe.start,
                    pe.end,
                    pe.layover
                FROM (
                    SELECT place_id,
                        trip_id,
                        start,
                        end,
                        layover
                    FROM place_event
                    UNION
                    SELECT place_id,
                        NULL AS trip_id,
                        NULL AS start,
                        NULL AS end,
                        0 AS layover
                    FROM place_permanent
                ) pe
                INNER JOIN place_identifier pi
                    ON pe.place_id = pi.id
                WHERE :CONDITIONS
                {$placeSortingStrategy->value}
            SQL;

            $homeTimeZone = $this->configurationService->getConfigurationEntry("homeLocation")["timezone"];
            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder();
            if ($placeId !== NULL) {
                $whereClauseBuilder->withClause("pi.id = ?", $placeId);
            }
            if ($year !== NULL) {
                $whereClauseBuilder->withClause("DATE_FORMAT(FROM_UNIXTIME(pe.start), '%Y') = ?", $year);
            }
            if ($tripId !== NULL) {
                $whereClauseBuilder->withClause("pe.trip_id = ?", $tripId);
            }
            if ($albumId !== NULL) {
                $album = $this->photoService->getAlbum($albumId);
                if ($album !== NULL) {                    
                    $albumDate = \DateTime::createFromFormat(self::DMY_DATE_FORMAT, $album->getPlaceDateString(), new \DateTimeZone($homeTimeZone));
                    $albumDate->setTime(0, 0);
                    $albumTimestamp = $albumDate->getTimestamp();
                    $whereClauseBuilder->withClause("pi.name = ? AND pe.start >= ? AND pe.start < ?", $album->getPlaceName(), $albumTimestamp, $albumTimestamp + self::ONE_DAY_SECONDS);
                }
                else {
                    $whereClauseBuilder->withClause("FALSE");
                }
            }
            if ($photoId !== NULL) {
                $album = $this->photoService->getAlbumForPhotoId($photoId);
                if ($album !== NULL) {
                    $albumDate = \DateTime::createFromFormat(self::DMY_DATE_FORMAT, $album->getPlaceDateString(), new \DateTimeZone($homeTimeZone));
                    $albumDate->setTime(0, 0);
                    $albumTimestamp = $albumDate->getTimestamp();
                    $whereClauseBuilder->withClause("pi.name = ? AND pe.start >= ? AND pe.start < ?", $album->getPlaceName(), $albumTimestamp, $albumTimestamp + self::ONE_DAY_SECONDS);
                }
                else {
                    $whereClauseBuilder->withClause("FALSE");
                }
            }
            if ($categoryId !== NULL) {
                $placeIds = $this->categoryService->getPlaceIdsForCategoryId($categoryId);
                if (count($placeIds) > 0) {
                    $whereClauseBuilder->withClause("pi.id IN (" . implode(",", array_fill(0, count($placeIds), "?")) . ")", ...$placeIds);
                }
                else {
                    $whereClauseBuilder->withClause("FALSE");
                }
            }
            if ($labelId !== NULL) {
                $placeIds = $this->labelService->getPlaceIdsForLabelId($labelId);
                if (count($placeIds) > 0) {
                    $whereClauseBuilder->withClause("pi.id IN (" . implode(",", array_fill(0, count($placeIds), "?")) . ")", ...$placeIds);
                }
                else {
                    $whereClauseBuilder->withClause("FALSE");
                }
            }
            if ($maxQuality !== NULL) {
                $whereClauseBuilder->withClause("pi.quality <= ?", $maxQuality);
            }
            if ($minStart !== NULL) {
                $whereClauseBuilder->withClause("(? <= pe.start OR pe.start IS NULL)", $minStart);
            }
            if ($maxEnd !== NULL) {
                $whereClauseBuilder->withClause("(pe.end <= ? OR pe.start IS NULL)", $maxEnd);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            $placeRows = $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getResultSet();

            $places = array();
            foreach ($placeRows as &$placeRow) {
                $permanentPlaceAlbums = array();
                if ($placeRow["trip_id"] === NULL) {
                    $permanentPlaceAlbums = array_filter($this->photoService->getAlbumsForPlace($placeRow["name"]),
                        fn($album) => ($albumId === NULL || $album->getId() == $albumId) 
                            && ($photoId === NULL || $this->photoService->getAlbumForPhotoId($photoId)?->getId() == $album->getId()));

                    if (($albumId !== NULL || $photoId !== NULL) && count($permanentPlaceAlbums) === 0) {
                        continue;
                    }
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
                    
                    $notes = array();
                    if (in_array(PlaceIncludedEntity::Notes->value, $includedEntities)) {
                        $notes = $this->noteService->getPlaceNotes($placeRow["id"]);                   
                    }
                    
                    $excerpt = NULL;
                    if (in_array(PlaceIncludedEntity::Excerpt->value, $includedEntities)) {
                        $excerpt = $placeRow["excerpt"];
                    }
                    
                    $places[$placeRow["id"]] = new Place($placeRow["id"], $placeRow["name"], $this->selectCountry($placeRow["country_category_id"]), $placeRow["latitude"], $placeRow["longitude"], $placeRow["timezone"],
                        $this->highlightService->getHighlight($placeRow["main_highlight_id"]), $placeRow["score"], $placeRow["quality"], $excerpt, $categories, $highlights, $labels, $notes, array());
                }
                
                if (in_array(PlaceIncludedEntity::Dates->value, $includedEntities)) {
                    if ($placeRow["trip_id"] === NULL) {
                        foreach ($permanentPlaceAlbums as &$permanentPlaceAlbum) {
                            $albumDate = \DateTime::createFromFormat(self::DMY_DATE_FORMAT, $permanentPlaceAlbum->getPlaceDateString(), new \DateTimeZone($homeTimeZone));
                            $albumDate->setTime(0, 0);
                            $albumTimestamp = $albumDate->getTimestamp();

                            if ($minStart <= $albumTimestamp && $albumTimestamp + self::ONE_DAY_SECONDS <= $maxEnd) {
                                $places[$placeRow["id"]]->addDate(new Date($albumTimestamp, $albumTimestamp + self::ONE_DAY_SECONDS, FALSE, NULL, NULL, $permanentPlaceAlbum, NULL));
                            }
                        }
                    }
                    else {
                        $weather = NULL;
                        $sun = NULL;
                        if ($placeRow["end"] > time()) {
                            $weather = $this->forecastService->getWeatherForecast($placeRow["id"], $placeRow["start"]);
                            $sun = $this->forecastService->getDaylightForecast($placeRow["id"], $placeRow["start"]);
                        }

                        $places[$placeRow["id"]]->addDate(new Date($placeRow["start"], $placeRow["end"], $placeRow["layover"] == 1, $weather, $sun,
                            $this->photoService->getAlbumForPlaceAndDate($placeRow["name"], $placeRow["start"]), $tripService->getTripIdentifierById($placeRow["trip_id"])));  
                    }
                }
            }

            return array_values($places);
        }
        
        public function selectCandidatePlaces(?string $placeId, ?string $categoryId, ?string $labelId, array $includedEntities) : array {
            $sql = <<<'SQL'
                SELECT pi.*
                FROM place_candidate pc
                INNER JOIN place_identifier pi
                    ON pc.place_id = pi.id
                WHERE :CONDITIONS
                ORDER BY name
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()
                ->withClause("pi.id NOT IN (SELECT place_id FROM place_event WHERE end < UNIX_TIMESTAMP())")
                ->withClause("pi.id NOT IN (SELECT place_id FROM place_permanent)");
            if ($placeId !== NULL) {
                $whereClauseBuilder->withClause("pi.id = ?", $placeId);
            }
            if ($categoryId !== NULL) {
                $placeIds = $this->categoryService->getPlaceIdsForCategoryId($categoryId);
                if (count($placeIds) > 0) {
                    $whereClauseBuilder->withClause("pi.id IN (" . implode(",", array_fill(0, count($placeIds), "?")) . ")", ...$placeIds);
                }
                else {
                    $whereClauseBuilder->withClause("FALSE");
                }
            }
            if ($labelId !== NULL) {
                $placeIds = $this->labelService->getPlaceIdsForLabelId($labelId);
                if (count($placeIds) > 0) {
                    $whereClauseBuilder->withClause("pi.id IN (" . implode(",", array_fill(0, count($placeIds), "?")) . ")", ...$placeIds);
                }
                else {
                    $whereClauseBuilder->withClause("FALSE");
                }
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $placeRows = $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getResultSet();

            $places = array();
            foreach ($placeRows as &$placeRow) {                             
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
                    
                $notes = array();
                if (in_array(PlaceIncludedEntity::Notes->value, $includedEntities)) {
                    $notes = $this->noteService->getPlaceNotes($placeRow["id"]);                   
                }

                $places[] = new Place($placeRow["id"], $placeRow["name"], $this->selectCountry($placeRow["country_category_id"]), $placeRow["latitude"],
                    $placeRow["longitude"], $placeRow["timezone"], NULL, $placeRow["score"] ?? 0, $placeRow["quality"], $excerpt, $categories, $highlights, $labels, $notes, array());
            }
            
            return $places;
        }
        
        public function selectCandidatePlacesForTrip(?string $categoryId, string $tripId, array $includedEntities) {         
            // TODO: Introduce a property for TripService $tripService.
            global $tripService;

            $sql = <<<'SQL'
                SELECT pi.*,
                    pce.start,
                    pce.end
                FROM place_candidate_event pce
                INNER JOIN place_identifier pi
                    ON pce.place_id = pi.id
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()->withClause("trip_id = ?", $tripId);
            if ($categoryId !== NULL) {
                $placeIds = $this->categoryService->getPlaceIdsForCategoryId($categoryId);
                if (count($placeIds) > 0) {
                    $whereClauseBuilder->withClause("pi.id IN (" . implode(",", array_fill(0, count($placeIds), "?")) . ")", ...$placeIds);
                }
                else {
                    $whereClauseBuilder->withClause("FALSE");
                }
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $placeRows = $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getResultSet();
                
            $trip = $tripService->getTripIdentifierById($tripId);

            $places = array();            
            foreach ($placeRows as &$placeRow) {
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
                    
                    $notes = array();
                    if (in_array(PlaceIncludedEntity::Notes->value, $includedEntities)) {
                        $notes = $this->noteService->getPlaceNotes($placeRow["place_id"]);                   
                    }

                    $places[$placeRow["id"]] = new Place($placeRow["id"], $placeRow["name"], $this->selectCountry($placeRow["country_category_id"]), $placeRow["latitude"],
                        $placeRow["longitude"], $placeRow["timezone"], NULL, $placeRow["score"] ?? 0, $placeRow["quality"], $excerpt, $categories, $highlights, $labels, $notes, array()); 
                }
                
                if (in_array(PlaceIncludedEntity::Dates->value, $includedEntities)) {
                    $places[$placeRow["id"]]->addDate(new Date($placeRow["start"], $placeRow["end"], FALSE, NULL, NULL, NULL, $trip));
                }
            }

            return array_values($places);
        }
        
        public function selectAllPlaceIdentifiers() : array {
            $sql = <<<'SQL'
                SELECT *
                FROM place_identifier
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getMappedResultSet(function($placeIdentifierRow) {
                    return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $this->selectCountry($placeIdentifierRow["country_category_id"]), $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"],
                        $placeIdentifierRow["timezone"], $this->highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["score"], $placeIdentifierRow["quality"], $placeIdentifierRow["excerpt"]);
                });
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
                $placeIdentifierRow["timezone"], $this->highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["score"], $placeIdentifierRow["quality"], $placeIdentifierRow["excerpt"]);
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
                $placeIdentifierRow["timezone"], $this->highlightService->getHighlight($placeIdentifierRow["main_highlight_id"]), $placeIdentifierRow["score"], $placeIdentifierRow["quality"], $placeIdentifierRow["excerpt"]);
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

        public function updatePlaceScore(string $placeId, float $score) : bool {
            $sql = <<<'SQL'
                UPDATE place_identifier
                SET score = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($score, $placeId)
                ->execute() === 1;
        }

        public function updatePlaceQuality(string $placeId, ?float $quality) : bool {
            $sql = <<<'SQL'
                UPDATE place_identifier
                SET quality = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($quality, $placeId)
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
                    excerpt,
                    score,
                    quality
                )
                VALUES (
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
            
            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeIdentifier->getName(), $this->categoryService->getCategoryIdentifier($placeIdentifier->getCountry())->getId(),
                    $placeIdentifier->getTimezone(), $placeIdentifier->getLatitude(), $placeIdentifier->getLongitude(), $placeIdentifier->getExcerpt(),
                    $placeIdentifier->getScore(), $placeIdentifier->getQuality())
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
        
        public function deleteStalePlaceIdentifiers() : int {
            $sql = <<<'SQL'
                DELETE
                FROM place_identifier
                WHERE id NOT IN (
                        SELECT place_id 
                        FROM place_candidate
                    )
                    AND id NOT IN (
                        SELECT place_id 
                        FROM place_event
                    ) 
                    AND id NOT IN (
                        SELECT place_id 
                        FROM place_permanent
                    ) 
                    AND id NOT IN (
                        SELECT place_id 
                        FROM place_candidate_event
                    )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }

        public function deleteVisitedCandidatePlaces() : int {
            $sql = <<<'SQL'
                DELETE 
                FROM place_candidate 
                WHERE place_id IN (
                        SELECT place_id 
                        FROM place_event 
                        WHERE end < UNIX_TIMESTAMP()
                    )
                    OR place_id IN (
                        SELECT place_id
                        FROM place_permanent
                    )
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
                    SELECT *
                    FROM place_event
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