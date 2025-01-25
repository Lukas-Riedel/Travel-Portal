<?php
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Category.php");
    require_once(dirname(__FILE__) . "/../lib/GeoPHP/geoPHP.inc");

    class CategoryService {
        public function updateCategories($placeIdentifier) : void {
            global $databaseProvider, $eventPublisher, $categoryService;

            // Obtain geographical regions.
            $geoRegions = $databaseProvider
                ->statementBuilder("SELECT * FROM region_geographical")
                ->getMappedResultSet(function($geoRegionRow) {
                    return array(
                        "categoryId" => $geoRegionRow["category_id"],
                        "country" => $geoRegionRow["country"],
                        "radius" => intval($geoRegionRow["radius"]),
                        "geoJson" => geoPHP::load($geoRegionRow["json"], "json"));
                });

            // Obtain composite regions.
            $compositeRegions = $databaseProvider
                ->statementBuilder("SELECT DISTINCT category_id FROM region_composite")
                ->getMappedResultSet(function ($compositeRegionRow) use (&$databaseProvider) {
                    return array(
                        "categoryId" => $compositeRegionRow["category_id"],
                        "includedCategoryIds" => $databaseProvider
                            ->statementBuilder("SELECT subject_category_id FROM region_composite WHERE category_id = ? AND type = 'INCLUDE'")
                            ->withParameters($compositeRegionRow["category_id"])
                            ->getResultSetForColumn("subject_category_id"),
                        "excludedCategoryIds" => $databaseProvider
                            ->statementBuilder("SELECT subject_category_id FROM region_composite WHERE category_id = ? AND type = 'EXCLUDE'")
                            ->withParameters($compositeRegionRow["category_id"])
                            ->getResultSetForColumn("subject_category_id"));
                });

            // Assign actual categories.
            $categoryIds = array();
            
            // Country category.
            $categoryIds[] = $categoryService->getOrCreateCategoryIdentifier($placeIdentifier->getCountry(), "COUNTRY")->getId(); 
        
            // Geographical region categories.
            $point = geoPHP::load("POINT (" . $placeIdentifier->getLongitude() . " " . $placeIdentifier->getLatitude() . ")", "wkt");
            foreach ($geoRegions as &$geoRegion) {
                if ($geoRegion["country"] === NULL || $geoRegion["country"] === $placeIdentifier->getCountry()) {
                    if ($geoRegion["geoJson"]->pointInPolygon($point)) {
                        $categoryIds[] = $geoRegion["categoryId"];
                    }
                    else if ($geoRegion["radius"] > 0) {
                        foreach ($this->getPointsOnCircle($placeIdentifier->getLatitude(), $placeIdentifier->getLongitude(), $geoRegion["radius"], 10) as &$pointOnCircle) {
                            $circlePoint = geoPHP::load("POINT (" . $pointOnCircle[1] . " " . $pointOnCircle[0] . ")", "wkt");
                            if ($geoRegion["geoJson"]->pointInPolygon($circlePoint)) {
                                $categoryIds[] = $geoRegion["categoryId"];
                                break;
                            }
                        }
                    }
                }
            }

            // Composite region categories.
            foreach ($compositeRegions as &$compositeRegion) {
                if ($this->arrayAny($compositeRegion["includedCategoryIds"], function ($includedCategoryId) use (&$categoryIds) { return in_array($includedCategoryId, $categoryIds); })
                    && $this->arrayEvery($compositeRegion["excludedCategoryIds"], function ($excludedCategoryId) use (&$categoryIds) { return !in_array($excludedCategoryId, $categoryIds); })) {
                    $categoryIds[] = $compositeRegion["categoryId"];
                }
            }

            $databaseProvider
                ->statementBuilder("DELETE FROM category WHERE place_id = ?")
                ->withParameters($placeIdentifier->getId())
                ->execute();

            foreach (array_unique($categoryIds) as &$categoryId) {  
                $databaseProvider
                    ->statementBuilder("INSERT INTO category (place_id, category_id) VALUES (?, ?)")
                    ->withParameters($placeIdentifier->getId(), $categoryId)
                    ->execute();

                $eventPublisher->publishCategoryStatisticsChangedEvent($categoryId);
            }
        }

        public function getCategoryIdentifierByName($name) : ?CategoryIdentifier {
            global $databaseProvider, $highlightService;
            
            $categoryIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM category_identifier WHERE name = ?")
                ->withParameters($name)
                ->getFirstRow();

            if ($categoryIdentifierRow === NULL) {
                return NULL;
            }

            return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], 
                $categoryIdentifierRow["category"], $highlightService->getHighlight($categoryIdentifierRow["main_highlight_id"]));
        }

        public function getCategoryIdentifier($categoryId) : ?CategoryIdentifier {
            global $databaseProvider, $highlightService;
            
            $categoryIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM category_identifier WHERE id = ?")
                ->withParameters($categoryId)
                ->getSingleRow();

            if ($categoryIdentifierRow === NULL) {
                return NULL;
            }
            
            return new CategoryIdentifier($categoryIdentifierRow["id"], $categoryIdentifierRow["name"], 
                $categoryIdentifierRow["category"], $highlightService->getHighlight($categoryIdentifierRow["main_highlight_id"]));
        }

        public function getCategoryIdentifiers($categoryIds) : array { 
            $categories = array();

            foreach ($categoryIds as &$categoryId) {
                $category = $this->getCategoryIdentifier($categoryId);
                if ($category !== NULL) {
                    $categories[] = $category;
                }
            }
            
            return $categories;
        }

        public function getCategory($categoryId) : ?Category {
            $categories = $this->doGetCategories($categoryId, NULL,
                array(CategoryIncludedEntity::Highlights->value, CategoryIncludedEntity::Statistics->value));
            return count($categories) === 1 ? $categories[0] : NULL;
        }

        public function getCategories($categoryCategories, $includedEntities) : array {
            return $this->doGetCategories(NULL, $categoryCategories, $includedEntities);
        }

        private function doGetCategories($categoryId, $categoryCategories, $includedEntities) : array {            
            global $databaseProvider, $highlightService, $statisticsService;
            
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if ($categoryId !== NULL) {
                $whereClauseBuilder->withClause("id = ?", $categoryId);
            }
            if ($categoryCategories !== NULL) {
                $whereClauseBuilder->withClause("FIND_IN_SET(category, ?)", $categoryCategories);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $categories = array();

            $categoryRows = $databaseProvider
                ->statementBuilder("SELECT * FROM category_identifier {{WHERE CLAUSE}}", $whereClause)
                ->getResultSet();

            foreach ($categoryRows as &$categoryRow) {                
                $highlights = array();
                if (in_array(CategoryIncludedEntity::Highlights->value, $includedEntities)) {
                    $highlights = $highlightService->getCategoryHighlights($categoryRow["id"]);                      
                }

                $stats = array();
                if (in_array(CategoryIncludedEntity::Statistics->value, $includedEntities)) {
                    $stats = $statisticsService->getCategoryStatistics($categoryRow["id"]);              
                }
                
                $categories[] = new Category($categoryRow["id"], $categoryRow["name"], $categoryRow["category"], 
                    $highlightService->getHighlight($categoryRow["main_highlight_id"]), $highlights, $stats);
            }

            return $categories;
        }

        public function updateCategoryMainHighlight($categoryId, $highlightIdentifier) : bool {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("UPDATE category_identifier SET main_highlight_id = ? WHERE id = ?")
                ->withParameters($highlightIdentifier, $categoryId)
                ->execute() === 1;
        }

        public function updateCategoryName($categoryId, $name) : bool {
            global $databaseProvider, $eventPublisher;
            
            $wasUpdated = $databaseProvider
                ->statementBuilder("UPDATE category_identifier SET name = ? WHERE id = ?")
                ->withParameters($name, $categoryId)
                ->execute() === 1;
                
            $eventPublisher->publishCategoryStatisticsChangedEvent($categoryId);

            return $wasUpdated;
        }
        
        public function getOrCreateCategoryIdentifier($name, $category) : CategoryIdentifier { 
            global $databaseProvider;

            $categoryIdentifier = $this->getCategoryIdentifierByName($name);
            if ($categoryIdentifier !== NULL) {
                return $categoryIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO category_identifier (name, category) VALUES (?, ?)")
                ->withParameters($name, $category)
                ->execute();
                
            return $this->getCategoryIdentifierByName($name);
        }

        public function createCompositeRegion($name, $category, $includedRegions, $excludedRegions) : CategoryIdentifier {
            global $databaseProvider, $eventPublisher, $configuration, $placeService;

            // Find out what can the composite regions consist of.
            $referencableRegionNames = $databaseProvider
                ->statementBuilder("SELECT name FROM category_identifier")
                ->getResultSetForColumn("name");
            
            foreach ($configuration["countries"] as $countryName => $countryConfigurationValue) {
                if (!in_array($countryName, $referencableRegionNames)) {
                    $referencableRegionNames[] = $countryName;
                }
            }

            // Verify that all referenced regions exist.
            foreach ($includedRegions as &$includedRegion) {
                if (!in_array($includedRegion, $referencableRegionNames)) {
                    throw new InvalidArgumentException("The included region '" . $includedRegion . "' does not exist.");
                }
            }

            foreach ($excludedRegions as &$excludedRegion) {
                if (!in_array($excludedRegion, $referencableRegionNames)) {
                    throw new InvalidArgumentException("The excluded region '" . $excludedRegion . "' does not exist.");
                }
            }

            // Create the region.
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category);    

            $databaseProvider
                ->statementBuilder("DELETE FROM region_composite WHERE category_id = ?")
                ->withParameters($categoryIdentifier->getId())
                ->execute();

            foreach ($includedRegions as &$includedRegion) {
                $subjectCategoryIdentifier = $this->getCategoryIdentifierByName($includedRegion);
                $databaseProvider
                    ->statementBuilder("INSERT INTO region_composite (category_id, subject_category_id, type) VALUES (?, ?, 'INCLUDE')")
                    ->withParameters($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId())
                    ->execute();

                $placeIdentifiers = $placeService->getPlaceIdentifiersByCategoryId($subjectCategoryIdentifier->getId());    
                foreach ($placeIdentifiers as &$placeIdentifier) {
                    $eventPublisher->publishPlaceCategoriesChangedEvent($placeIdentifier->getId());
                }
            }

            foreach ($excludedRegions as &$excludedRegion) {
                $subjectCategoryIdentifier = $this->getCategoryIdentifierByName($excludedRegion);
                $databaseProvider
                    ->statementBuilder("INSERT INTO region_composite (category_id, subject_category_id, type) VALUES (?, ?, 'EXCLUDE')")
                    ->withParameters($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId())
                    ->execute();
            }
    
            $eventPublisher->publishCategoryCreatedEvent($categoryIdentifier->getId());
            
            return $categoryIdentifier;
        }

        public function createGeographicalRegion($name, $country, $category, $radius, $geoJson) : CategoryIdentifier {
            global $databaseProvider, $eventPublisher, $placeService;
                                    
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category); 

            $databaseProvider
                ->statementBuilder("DELETE FROM region_geographical WHERE category_id = ? AND country " . $databaseProvider->getIsNullOrEqualTo($country))
                ->withParameters($categoryIdentifier->getId())
                ->execute();
                
            $databaseProvider
                ->statementBuilder("INSERT INTO region_geographical (category_id, country, json, radius) VALUES (?, ?, ?, ?)")
                ->withParameters($categoryIdentifier->getId(), $country, $geoJson, $radius)
                ->execute();

            $placeIdentifiers = $country === NULL
                ? $placeService->getAllPlaceIdentifiers()
                : $placeService->getPlaceIdentifiersByCountry($country);

            foreach ($placeIdentifiers as &$placeIdentifier) {
                $eventPublisher->publishPlaceCategoriesChangedEvent($placeIdentifier->getId());
            }
    
            $eventPublisher->publishCategoryCreatedEvent($categoryIdentifier->getId());
            
            return $categoryIdentifier;
        }

        public function createGeographicalRegionExtensionRegion($name, $country, $category, $latitude, $longitude) : CategoryIdentifier {
            global $databaseProvider, $eventPublisher, $placeService;
            
            $geoJson = json_encode(array(
                "type" => "Feature", 
                "geometry" => array(
                    "type" => "Point", 
                    "coordinates" => array(
                        floatval($longitude), 
                        floatval($latitude)))), TRUE);
            
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category);

            $databaseProvider
                ->statementBuilder("INSERT INTO region_geographical (category_id, country, json, radius) VALUES (?, ?, ?, 0)")
                ->withParameters($categoryIdentifier->getId(), $country, $geoJson)
                ->execute();                

            $placeIdentifiers = $placeService->getPlaceIdentifiersByCoordinates($latitude, $longitude);

            foreach ($placeIdentifiers as &$placeIdentifier) {
                $eventPublisher->publishPlaceCategoriesChangedEvent($placeIdentifier->getId());
            }
            
            return $categoryIdentifier;
        }

        public function updateRegionAreas() : void {
            global $databaseProvider;
            
            $areas = array();

            $geoRegionRows = $databaseProvider
                ->statementBuilder("SELECT * FROM region_geographical WHERE json NOT LIKE '%Point%'")
                ->getResultSet();

            foreach ($geoRegionRows as &$geoRegionRow) {
                $area = geoPHP::load($geoRegionRow["json"], "json")->getArea();
                $areas[$geoRegionRow["category_id"]] = $area;

                if ($geoRegionRow["country"] !== NULL) {
                    $countryCategoryId = $this->getCategoryIdentifierByName($geoRegionRow["country"]);

                    if ($countryCategoryId !== NULL) {
                        if (!array_key_exists($countryCategoryId->getId(), $areas)) {
                            $areas[$countryCategoryId->getId()] = 0;
                        }
                        $areas[$countryCategoryId->getId()] += $area;
                    }
                }
            }

            $compositeRegionRows = $databaseProvider
                ->statementBuilder("SELECT * FROM region_composite")
                ->getResultSet();

            foreach ($compositeRegionRows as &$compositeRegionRow) {
                if (!array_key_exists($compositeRegionRow["category_id"], $areas)) {
                    $areas[$compositeRegionRow["category_id"]] = 0;
                }

                if (array_key_exists($compositeRegionRow["subject_category_id"], $areas)) {
                    if ($compositeRegionRow["type"] === "INCLUDE") {
                        $areas[$compositeRegionRow["category_id"]] += $areas[$compositeRegionRow["subject_category_id"]];
                    }
                    else if ($compositeRegionRow["type"] === "EXCLUDE") {
                        $areas[$compositeRegionRow["category_id"]] -= $areas[$compositeRegionRow["subject_category_id"]];
                    }
                }
            }

            $databaseProvider
                ->statementBuilder("DELETE FROM region_area")
                ->execute();

            foreach ($areas as $categoryId => $area) {
                $databaseProvider
                    ->statementBuilder("INSERT INTO region_area (category_id, area) VALUES (?, ?)")
                    ->withParameters($categoryId, $area)
                    ->execute();
            }
        }

        private function getPointsOnCircle($x, $y, $radiusInKms, $pointsCount) : array {    
            $points = array();
    
            for ($i = 0; $i < $pointsCount; $i++) {
                $points[] = array($x + $this->positionX($pointsCount, $i, $radiusInKms / 111), $y + $this->positionY($pointsCount, $i, $radiusInKms / 111));
            }
    
            return $points;
        }
    
        private function positionX($numItems, $thisNum, $r) : float {
            $alpha = 360 / $numItems;
            $angle = $alpha * $thisNum;
            $x = $r * cos(deg2rad($angle));
            return $x;
        }
          
        private function positionY($numItems, $thisNum, $r) : float {
            $alpha = 360 / $numItems;
            $angle = $alpha * $thisNum;
            $y = $r * sin(deg2rad($angle));
            return $y;
        }

        private function arrayAny($array, $fn) : bool {
            foreach ($array as &$value) {
                if ($fn($value)) {
                    return TRUE;
                }
            }
            return FALSE;
        }
    
        private function arrayEvery($array, $fn) : bool {
            foreach ($array as &$value) {
                if (!$fn($value)) {
                    return FALSE;
                }
            }
            return TRUE;
        }

        public function onCategoryCreated($message) : void {
            $this->updateRegionAreas();
        }

        public function onPlaceCategoriesChanged($message) : void {
            global $placeService;

            $placeIdentifier = $placeService->getPlaceIdentifierById($message["placeId"]);
            $this->updateCategories($placeIdentifier);
        }
    }

    enum CategoryIncludedEntity : string {
        case Statistics = "STATISTICS";
        case Highlights = "HIGHLIGHTS";
    }
?>