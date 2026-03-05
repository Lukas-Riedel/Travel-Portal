<?php
    namespace Core\Service\Category;

    use Core\Client\Database\DatabaseClient;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Statistics\StatisticsService;

    class CategoryMapper {

        private readonly DatabaseClient $databaseClient;
        private readonly HighlightService $highlightService;
        private readonly StatisticsService $statisticsService;

        public function __construct(DatabaseClient $databaseClient, HighlightService $highlightService,
            StatisticsService $statisticsService) {
            $this->databaseClient = $databaseClient;
            $this->highlightService = $highlightService;
            $this->statisticsService = $statisticsService;
        }

        public function selectAllCategoryNames() : array {            
            $sql = <<<'SQL'
                SELECT name
                FROM category_identifier
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getResultSetForColumn("name");
        }

        public function selectCategoryIdentifier(string $name) : ?CategoryIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM category_identifier
                WHERE name = ?
            SQL;

            $categoryIdentifierRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($name)
                ->getFirstRow();

            if ($categoryIdentifierRow === null) {
                return null;
            }

            $metadata = $categoryIdentifierRow["color"] === null && $categoryIdentifierRow["unicode"] === null && $categoryIdentifierRow["public_holidays_calendar"] === null
                ? null : new CategoryMetadata($categoryIdentifierRow["color"], $categoryIdentifierRow["unicode"], $categoryIdentifierRow["public_holidays_calendar"]);
            return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], CategoryCategory::from($categoryIdentifierRow["category"]),
                $metadata, $this->highlightService->getHighlight($categoryIdentifierRow["main_highlight_id"]));
        }

        public function selectCategoryIdentifiersByIds(array $categoryIds) : array {
            $sql = <<<SQL
                SELECT *
                FROM category_identifier
                WHERE id IN ({$this->databaseClient->getPlaceholdersSequence(count($categoryIds))})
            SQL;

            $categoryIdentifierRows = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters(...$categoryIds)
                ->getResultSet();
            
            $mainHighlightIds = array_filter(array_map(fn($placeRow) => $placeRow["main_highlight_id"], $categoryIdentifierRows), fn($highlightId) => $highlightId !== null);
            
            $mainHighlights = array();
            foreach ($this->highlightService->getHighlights($mainHighlightIds) as &$mainHighlight) {
                $mainHighlights[$mainHighlight->getId()] = $mainHighlight;
            }

            $categoryIdentifiers = array();
            foreach ($categoryIdentifierRows as &$categoryIdentifierRow) {
                $metadata = $categoryIdentifierRow["color"] === null && $categoryIdentifierRow["unicode"] === null && $categoryIdentifierRow["public_holidays_calendar"] === null
                    ? null : new CategoryMetadata($categoryIdentifierRow["color"], $categoryIdentifierRow["unicode"], $categoryIdentifierRow["public_holidays_calendar"]);
                $categoryIdentifiers[] = new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], CategoryCategory::from($categoryIdentifierRow["category"]),
                    $metadata, $mainHighlights[$categoryIdentifierRow["main_highlight_id"]] ?? null);                    
            }

            return $categoryIdentifiers;
        }

        public function selectCategoryIdentifierById(string $categoryId) : ?CategoryIdentifier {
            $sql = <<<'SQL'
                SELECT *
                FROM category_identifier
                WHERE id = ?
            SQL;

            $categoryIdentifierRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($categoryId)
                ->getSingleRow();

            if ($categoryIdentifierRow === null) {
                return null;
            }

            $metadata = $categoryIdentifierRow["color"] === null && $categoryIdentifierRow["unicode"] === null && $categoryIdentifierRow["public_holidays_calendar"] === null
                ? null : new CategoryMetadata($categoryIdentifierRow["color"], $categoryIdentifierRow["unicode"], $categoryIdentifierRow["public_holidays_calendar"]);
            return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], CategoryCategory::from($categoryIdentifierRow["category"]),
                $metadata, $this->highlightService->getHighlight($categoryIdentifierRow["main_highlight_id"]));
        }

        public function selectCategories(?string $categoryId, ?string $countryCategoryId, array $categoryCategories, array $includedEntities) : array {
            $sql = <<<'SQL'
                SELECT *
                FROM category_identifier ci
                WHERE :CONDITIONS
                ORDER BY name
            SQL;

            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder();
            if (count($categoryCategories) > 0) {
                $whereClauseBuilder->withClause("ci.category = ANY(STRING_TO_ARRAY(?, ','))", implode(",", $categoryCategories));
            }
            if ($categoryId !== null) {
                $whereClauseBuilder->withClause("ci.id = ?", $categoryId);
            }
            if ($countryCategoryId !== null) {
                $whereClauseBuilder->withClause("EXISTS (SELECT 1 FROM region_geographical rg WHERE ci.id = rg.category_id AND rg.country_category_id = ?)", $countryCategoryId);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $categoryRows = $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getResultSet();
            
            $mainHighlightIds = array_filter(array_map(fn($placeRow) => $placeRow["main_highlight_id"], $categoryRows), fn($highlightId) => $highlightId !== null);
            
            $mainHighlights = array();
            foreach ($this->highlightService->getHighlights($mainHighlightIds) as &$mainHighlight) {
                $mainHighlights[$mainHighlight->getId()] = $mainHighlight;
            }

            $categories = array();
            foreach ($categoryRows as &$categoryRow) {
                $highlights = array();
                if (in_array(CategoryIncludedEntity::Highlights->value, $includedEntities)) {
                    $highlights = $this->highlightService->getCategoryHighlights($categoryRow["id"]);                      
                }

                $statistics = array();
                if (in_array(CategoryIncludedEntity::Statistics->value, $includedEntities)) {
                    $statistics = $this->statisticsService->getCategoryStatistics($categoryRow["id"]);              
                }
                
                $metadata = $categoryRow["color"] === null && $categoryRow["unicode"] === null && $categoryRow["public_holidays_calendar"] === null
                    ? null : new CategoryMetadata($categoryRow["color"], $categoryRow["unicode"], $categoryRow["public_holidays_calendar"]);
                $categories[] = new Category($categoryRow["id"], $categoryRow["name"], CategoryCategory::from($categoryRow["category"]), $metadata,
                    $mainHighlights[$categoryRow["main_highlight_id"]] ?? null, $highlights, $statistics);
            }

            return $categories;
        }

        public function selectGeographicalRegions(?string $name) : array {            
            $sql = <<<'SQL'
                SELECT rg.*
                FROM region_geographical rg
                INNER JOIN category_identifier ci
                    ON rg.category_id = ci.id
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder();
            if ($name !== null) {
                $whereClauseBuilder->withClause("ci.name = ?", $name);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($geographicalRegionRow) {
                    return new GeographicalRegion($this->selectCategoryIdentifierById($geographicalRegionRow["category_id"]), 
                        $geographicalRegionRow["country_category_id"] === null ? null : $this->selectCategoryIdentifierById($geographicalRegionRow["country_category_id"]),
                        intval($geographicalRegionRow["radius"]), json_decode($geographicalRegionRow["json"], true));
                });
        }

        public function selectAllNonTrivialGeographicalRegions() : array {            
            $sql = <<<'SQL'
                SELECT *
                FROM region_geographical
                WHERE NOT (
                    json->>'type' = 'Feature'
                    AND json->'geometry'->>'type' = 'Point'
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->getMappedResultSet(function($geographicalRegionRow) {
                    return new GeographicalRegion($this->selectCategoryIdentifierById($geographicalRegionRow["category_id"]), 
                        $geographicalRegionRow["country_category_id"] === null ? null : $this->selectCategoryIdentifierById($geographicalRegionRow["country_category_id"]),
                        intval($geographicalRegionRow["radius"]), json_decode($geographicalRegionRow["json"], true));
                });
        }

        public function selectCompositeRegions(?string $name) : array {            
            $sql = <<<'SQL'
                SELECT DISTINCT rc.category_id
                FROM region_composite rc
                INNER JOIN category_identifier ci
                    ON rc.category_id = ci.id
                WHERE :CONDITIONS
            SQL;
            
            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder();
            if ($name !== null) {
                $whereClauseBuilder->withClause("ci.name = ?", $name);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getMappedResultSet(function($compositeRegionRow) {
                    return new CompositeRegion($this->selectCategoryIdentifierById($compositeRegionRow["category_id"]),
                        $this->selectIncludedCategoryIdentifiers($compositeRegionRow["category_id"]),
                        $this->selectExcludedCategoryIdentifiers($compositeRegionRow["category_id"]));
                });
        }

        private function selectIncludedCategoryIdentifiers(string $compositeRegionCategoryId) : array {        
            $sql = <<<'SQL'
                SELECT ci.*
                FROM region_composite re
                INNER JOIN category_identifier ci
                    ON re.subject_category_id = ci.id
                WHERE re.category_id = ?
                    AND re.included
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($compositeRegionCategoryId)
                ->getMappedResultSet(function($categoryIdentifierRow) {                    
                    $metadata = $categoryIdentifierRow["color"] === null && $categoryIdentifierRow["unicode"] === null && $categoryIdentifierRow["public_holidays_calendar"] === null
                        ? null : new CategoryMetadata($categoryIdentifierRow["color"], $categoryIdentifierRow["unicode"], $categoryIdentifierRow["public_holidays_calendar"]);
                    return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], CategoryCategory::from($categoryIdentifierRow["category"]),
                        $metadata, $this->highlightService->getHighlight($categoryIdentifierRow["main_highlight_id"]));
                });
        }

        // TODO: This is a copy-paste of selectIncludedCategoryIdentifiers, just with a different value in the WHERE clause.
        private function selectExcludedCategoryIdentifiers(string $compositeRegionCategoryId) : array {        
            $sql = <<<'SQL'
                SELECT ci.*
                FROM region_composite re
                INNER JOIN category_identifier ci
                    ON re.subject_category_id = ci.id
                WHERE re.category_id = ?
                    AND NOT re.included
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($compositeRegionCategoryId)
                ->getMappedResultSet(function($categoryIdentifierRow) {                    
                    $metadata = $categoryIdentifierRow["color"] === null && $categoryIdentifierRow["unicode"] === null && $categoryIdentifierRow["public_holidays_calendar"] === null
                        ? null : new CategoryMetadata($categoryIdentifierRow["color"], $categoryIdentifierRow["unicode"], $categoryIdentifierRow["public_holidays_calendar"]);
                    return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], CategoryCategory::from($categoryIdentifierRow["category"]),
                        $metadata, $this->highlightService->getHighlight($categoryIdentifierRow["main_highlight_id"]));
                });
        }

        public function selectCategoryIdsForPlace(string $placeId) : array {            
            $sql = <<<'SQL'
                SELECT category_id
                FROM category
                WHERE place_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId)
                ->getResultSetForColumn("category_id");
        }

        public function selectCategoryIdsForCategory(?CategoryCategory $category) : array {
            $sql = <<<'SQL'
                SELECT id
                FROM category_identifier
                WHERE :CONDITIONS
            SQL;

            $whereClauseBuilder = $this->databaseClient->whereClauseBuilder();
            if ($category !== null) {
                $whereClauseBuilder->withClause("category = ?", $category->value);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            return $this->databaseClient
                ->statementBuilder($sql, $whereClause)
                ->getResultSetForColumn("id");
        }

        public function selectPlaceIdsForCategory(string $categoryId) : array {            
            $sql = <<<'SQL'
                SELECT place_id
                FROM category
                WHERE category_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($categoryId)
                ->getResultSetForColumn("place_id");
        }

        public function selectCategoryIdsForAllPlaceIds() : array {
            $sql = <<<'SQL'
                SELECT c.*
                FROM category c
                LEFT JOIN region_area ra
                    ON c.category_id = ra.category_id
                ORDER BY ra.area DESC
            SQL;

            $categoryRows = $this->databaseClient
                ->statementBuilder($sql)
                ->getResultSet();

            $placeIdsToCategoryIds = array();
            foreach ($categoryRows as &$categoryRow) {
                $placeIdsToCategoryIds[$categoryRow["place_id"]] ??= array();
                $placeIdsToCategoryIds[$categoryRow["place_id"]][] = $categoryRow["category_id"];
            }

            return $placeIdsToCategoryIds;
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

            return $this->databaseClient
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($categoryId, $area)
                ->execute() === 1;
        }

        public function insertGeographicalRegion(GeographicalRegion $geographicalRegion) : bool {    
            $sql = <<<'SQL'
                INSERT INTO region_geographical (
                    category_id,
                    country_category_id,
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($geographicalRegion->getCategory()->getId(), $geographicalRegion->getCountryCategory()?->getId(),
                    $geographicalRegion->getRadius(), json_encode($geographicalRegion->getGeoJson()))
                ->execute() === 1;
        }

        public function insertCompositeRegionInclusion(string $categoryId, string $subjectCategoryId) : bool {    
            $sql = <<<'SQL'
                INSERT INTO region_composite (
                    category_id,
                    subject_category_id,
                    included
                )
                VALUES (
                    ?,
                    ?,
                    true
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($categoryId, $subjectCategoryId)
                ->execute() === 1;
        }

        public function insertCompositeRegionExclusion(string $categoryId, string $subjectCategoryId) : bool {    
            $sql = <<<'SQL'
                INSERT INTO region_composite (
                    category_id,
                    subject_category_id,
                    included
                )
                VALUES (
                    ?,
                    ?,
                    false
                )
            SQL;

            return $this->databaseClient
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
                RETURNING id
            SQL;

            $id = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($categoryIdentifier->getName(), $categoryIdentifier->getCategory()->value)
                ->getSingleColumn("id");

            if ($id === null) {
                return false;
            }
            
            $categoryIdentifier->setId($id);
            return true;
        }

        public function updateCategoryMainHighlight(string $categoryId, ?string $highlightIdentifier) : bool {
            $sql = <<<'SQL'
                UPDATE category_identifier
                SET main_highlight_id = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($highlightIdentifier, $categoryId)
                ->execute() === 1;
        }

        public function updateCategoryCategory(string $categoryId, CategoryCategory $category) : bool {
            $sql = <<<'SQL'
                UPDATE category_identifier
                SET category = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($category->value, $categoryId)
                ->execute() === 1;
        }

        public function updateCategoryName(string $categoryId, string $name) : bool {
            $sql = <<<'SQL'
                UPDATE category_identifier
                SET name = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($name, $categoryId)
                ->execute() === 1;
        }

        public function updateCategoryColor(string $categoryId, string $color) : bool {
            $sql = <<<'SQL'
                UPDATE category_identifier
                SET color = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($color, $categoryId)
                ->execute() === 1;
        }

        public function updateCategoryUnicode(string $categoryId, string $unicode) : bool {
            $sql = <<<'SQL'
                UPDATE category_identifier
                SET unicode = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($unicode, $categoryId)
                ->execute() === 1;
        }

        public function updateCategoryPublicHolidaysCalendar(string $categoryId, string $publicHolidaysCalendar) : bool {
            $sql = <<<'SQL'
                UPDATE category_identifier
                SET public_holidays_calendar = ?
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($publicHolidaysCalendar, $categoryId)
                ->execute() === 1;
        }

        public function deleteCategories(string $placeId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM category
                WHERE place_id = ?
            SQL;

            return $this->databaseClient
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

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($categoryId)
                ->execute();
        }

        public function deleteCompositeRegionReferences(string $categoryId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM region_composite
                WHERE subject_category_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($categoryId)
                ->execute();
        }

        public function deleteGeographicalRegion(string $categoryId) : int {
            $sql = <<<SQL
                DELETE
                FROM region_geographical
                WHERE category_id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($categoryId)
                ->execute();
        }

        public function deleteAllRegionAreas() : int {
            $sql = <<<'SQL'
                DELETE
                FROM region_area
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }    
        
        public function deleteCategoryIdentifier(string $categoryId) : int {
            $sql = <<<'SQL'
                DELETE
                FROM category_identifier
                WHERE id = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($categoryId)
                ->execute();
        }
    }
?>