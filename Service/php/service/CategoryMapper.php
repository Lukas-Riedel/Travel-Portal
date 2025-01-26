<?php
    class CategoryMapper {

        private readonly DatabaseProvider $databaseProvider;

        private readonly HighlightService $highlightService;
        private readonly StatisticsService $statisticsService;

        public function __construct(DatabaseProvider $databaseProvider, HighlightService $highlightService,
            StatisticsService $statisticsService) {
            $this->databaseProvider = $databaseProvider;
            $this->highlightService = $highlightService;
            $this->statisticsService = $statisticsService;
        }

        public function selectAllCategoryNames() : array {            
            $sql = <<<'SQL'
                SELECT name
                FROM category_identifier
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getResultSetForColumn("name");
        }

        public function selectCategoryIdentifierByName(string $name) : ?CategoryIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM category_identifier
                WHERE name = ?
            SQL;

            $categoryIdentifierRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($name)
                ->getFirstRow();

            if ($categoryIdentifierRow === NULL) {
                return NULL;
            }

            return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], 
                $categoryIdentifierRow["category"], $this->highlightService->getHighlight($categoryIdentifierRow["main_highlight_id"]));
        }

        public function selectCategoryIdentifier(string $categoryId) : ?CategoryIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM category_identifier
                WHERE id = ?
            SQL;

            $categoryIdentifierRow = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($categoryId)
                ->getSingleRow();

            if ($categoryIdentifierRow === NULL) {
                return NULL;
            }

            return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], 
                $categoryIdentifierRow["category"], $this->highlightService->getHighlight($categoryIdentifierRow["main_highlight_id"]));
        }

        public function selectCategories(?string $categoryId, array $categoryCategories, array $includedEntities) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM category_identifier
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = $this->databaseProvider->whereClauseBuilder()
                ->withClause("FIND_IN_SET(category, ?)", implode(",", $categoryCategories));
            if ($categoryId !== NULL) {
                $whereClauseBuilder->withClause("id = ?", $categoryId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseProvider
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function ($categoryRow) use ($includedEntities) {
                    $highlights = array();
                    if (in_array(CategoryIncludedEntity::Highlights->value, $includedEntities)) {
                        $highlights = $this->highlightService->getCategoryHighlights($categoryRow["id"]);                      
                    }
    
                    $statistics = array();
                    if (in_array(CategoryIncludedEntity::Statistics->value, $includedEntities)) {
                        $statistics = $this->statisticsService->getCategoryStatistics($categoryRow["id"]);              
                    }
                    
                    return new Category($categoryRow["id"], $categoryRow["name"], $categoryRow["category"], 
                        $this->highlightService->getHighlight($categoryRow["main_highlight_id"]), $highlights, $statistics);
                });
        }

        public function selectAllGeographicalRegions() : array {            
            $sql = <<<'SQL'
                SELECT *
                FROM region_geographical
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getMappedResultSet(function($geographicalRegionRow) {
                    return new GeographicalRegion($geographicalRegionRow["category_id"], $geographicalRegionRow["country"],
                        intval($geographicalRegionRow["radius"]), geoPHP::load($geographicalRegionRow["json"], "json"));
                });
        }

        public function selectAllNonTrivialGeographicalRegions() : array {            
            $sql = <<<'SQL'
                SELECT *
                FROM region_geographical
                WHERE json NOT LIKE '%Point%'
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getMappedResultSet(function($geographicalRegionRow) {
                    return new GeographicalRegion($geographicalRegionRow["category_id"], $geographicalRegionRow["country"],
                        intval($geographicalRegionRow["radius"]), geoPHP::load($geographicalRegionRow["json"], "json"));
                });
        }

        public function selectAllCompositeRegions() : array {            
            $sql = <<<'SQL'
                SELECT DISTINCT category_id
                FROM region_composite
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->getMappedResultSet(function($compositeRegionRow) {
                    return new CompositeRegion($compositeRegionRow["category_id"],
                        $this->selectIncludedCategoryIds($compositeRegionRow["category_id"]),
                        $this->selectExcludedCategoryIds($compositeRegionRow["category_id"]));
                });
        }

        private function selectIncludedCategoryIds(string $compositeRegionCategoryId) : array {        
            $sql = <<<'SQL'
                SELECT subject_category_id
                FROM region_composite
                WHERE category_id = ?
                    AND type = 'INCLUDE'
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($compositeRegionCategoryId)
                ->getResultSetForColumn("subject_category_id");
        }

        private function selectExcludedCategoryIds(string $compositeRegionCategoryId) : array {        
            $sql = <<<'SQL'
                SELECT subject_category_id
                FROM region_composite
                WHERE category_id = ?
                    AND type = 'EXCLUDE'
            SQL;
    
            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($compositeRegionCategoryId)
                ->getResultSetForColumn("subject_category_id");
        }

        public function insertCategory(string $placeId, string $categoryId) : bool {    
            $sql = <<<'SQL'
                INSERT INTO category (
                    place_id,
                    category_id
                )
                VALUES (
                    ?,
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeId, $categoryId)
                ->execute() === 1;
        }

        public function insertRegionArea(string $categoryId, float $area) : bool {    
            $sql = <<<'SQL'
                INSERT INTO region_area (
                    category_id,
                    area
                )
                VALUES (
                    ?,
                    ?
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($categoryId, $area)
                ->execute() === 1;
        }

        public function insertGeographicalRegion(GeographicalRegion $geographicalRegion) : bool {    
            $sql = <<<'SQL'
                INSERT INTO region_geographical (
                    category_id,
                    country,
                    radius,
                    json
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
                ->withParameters($geographicalRegion->getCategoryId(), $geographicalRegion->getCountry(),
                    $geographicalRegion->getRadius(), $geographicalRegion->getGeoJson())
                ->execute() === 1;
        }

        public function insertCompositeRegionInclusion(string $categoryId, string $subjectCategoryId) : bool {    
            $sql = <<<'SQL'
                INSERT INTO region_composite (
                    category_id,
                    subject_category_id,
                    type
                )
                VALUES (
                    ?,
                    ?,
                    'INCLUDE'
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($categoryId, $subjectCategoryId)
                ->execute() === 1;
        }

        public function insertCompositeRegionExclusion(string $categoryId, string $subjectCategoryId) : bool {    
            $sql = <<<'SQL'
                INSERT INTO region_composite (
                    category_id,
                    subject_category_id,
                    type
                )
                VALUES (
                    ?,
                    ?,
                    'EXCLUDE'
                )
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($categoryId, $subjectCategoryId)
                ->execute() === 1;
        }

        public function insertCategoryIdentifier(CategoryIdentifier $categoryIdentifier) : bool {    
            $sql = <<<'SQL'
                INSERT INTO category_identifier (
                    name,
                    category
                )
                VALUES (
                    ?,
                    ?
                )
            SQL;

            $wasInserted = $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($categoryIdentifier->getName(), $categoryIdentifier->getCategory())
                ->execute() === 1;

            if ($wasInserted) {
                $categoryIdentifier->setId($this->databaseProvider->getLastInsertedId());
            }
            
            return $wasInserted;
        }

        public function updateCategoryMainHighlight(string $categoryId, string $highlightIdentifier) : bool {
            $sql = <<<'SQL'
                UPDATE category_identifier
                SET main_highlight_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($highlightIdentifier, $categoryId)
                ->execute() === 1;
        }

        public function updateCategoryName(string $categoryId, string $name) : bool {
            $sql = <<<'SQL'
                UPDATE category_identifier
                SET name = ?
                WHERE id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($name, $categoryId)
                ->execute() === 1;
        }

        public function deleteCategories(string $placeId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM category
                WHERE place_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($placeId)
                ->execute();
        }

        public function deleteCompositeRegion(string $categoryId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM region_composite
                WHERE category_id = ?
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($categoryId)
                ->execute();
        }

        public function deleteGeographicalRegion(string $categoryId, string $country) : int {
            $sql = <<<SQL
                DELETE
                FROM region_geographical
                WHERE category_id = ?
                    AND {$this->databaseProvider->getIsNullOrEqualTo($country)}
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->withParameters($categoryId)
                ->execute();
        }

        public function deleteAllRegionAreas() : int {
            $sql = <<<'SQL'
                DELETE
                FROM region_area
            SQL;

            return $this->databaseProvider
                ->statementBuilder($sql)
                ->execute();
        }
    }
?>