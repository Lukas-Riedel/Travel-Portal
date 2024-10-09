<?php
    require_once(dirname(__FILE__) . "/../GeoPHP/geoPHP.inc");
    require_once(dirname(__FILE__) . "/GetCategoryIdentifierProcessor.php");

    class UpdateCategoriesProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $schedulingProvider, $configuration;

            $getCategoryIdentifierProcessor = new GetCategoryIdentifierProcessor();

            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($input["placeId"])
                ->getSingleRow();

            if ($placeIdentifierRow == NULL) {
                throw new InvalidArgumentException("A place with the identifier " . $input["placeId"] . " does not exist.");
            }

            // Obtain geographical regions.
            $geoRegionRows = $databaseProvider
                ->statementBuilder("SELECT * FROM region_geographical")
                ->getResultSet();

            $geoRegions = array();
            foreach ($geoRegionRows as &$geoRegionRow) {
                $geoRegions[] = array(
                    "categoryId" => $geoRegionRow["category_id"],
                    "country" => $geoRegionRow["country"],
                    "radius" => intval($geoRegionRow["radius"]),
                    "geoJson" => geoPHP::load($geoRegionRow["json"], "json"));
            }

            // Obtain composite regions.
            $compositeRegionRows = $databaseProvider
                ->statementBuilder("SELECT DISTINCT category_id FROM region_composite")
                ->getResultSet();

            $compositeRegions = array();
            foreach ($compositeRegionRows as &$compositeRegionRow) {
                $compositeRegions[] = array(
                    "categoryId" => $compositeRegionRow["category_id"],
                    "includedCategoryIds" => $databaseProvider
                        ->statementBuilder("SELECT subject_category_id FROM region_composite WHERE category_id = ? AND type = 'INCLUDE'")
                        ->withParameters($compositeRegionRow["category_id"])
                        ->getResultSetForColumn("subject_category_id"),
                    "excludedCategoryIds" => $databaseProvider
                        ->statementBuilder("SELECT subject_category_id FROM region_composite WHERE category_id = ? AND type = 'EXCLUDE'")
                        ->withParameters($compositeRegionRow["category_id"])
                        ->getResultSetForColumn("subject_category_id"));
            }

            // Assign actual categories.
            $placeCategoryIds = array();
            
            // Country category.
            $placeCategoryIds[] = $getCategoryIdentifierProcessor
                ->process(array(
                    "name" => $placeIdentifierRow["country"],
                    "category" => "COUNTRY"))->getId();
        
            // Geographical region categories.
            $point = geoPHP::load("POINT (" . $placeIdentifierRow["longitude"] . " " . $placeIdentifierRow["latitude"] . ")", "wkt");
            foreach ($geoRegions as $region) {
                if ($region["country"] == NULL || $region["country"] == $placeIdentifierRow["country"]) {
                    if ($region["geoJson"]->pointInPolygon($point)) {
                        $placeCategoryIds[] = $region["categoryId"];
                    }
                    else if ($region["radius"] > 0) {
                        foreach ($this->getPointsOnCircle($placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"], $region["radius"], 10) as &$pointOnCircle) {
                            $circlePoint = geoPHP::load("POINT (" . $pointOnCircle[1] . " " . $pointOnCircle[0] . ")", "wkt");
                            if ($region["geoJson"]->pointInPolygon($circlePoint)) {
                                $placeCategoryIds[] = $region["categoryId"];
                                break;
                            }
                        }
                    }
                }
            }

            // Composite region categories.
            foreach ($compositeRegions as $region) {
                if ($this->arrayAny($region["includedCategoryIds"], function ($includedCategoryId) use (&$placeCategoryIds) { return in_array($includedCategoryId, $placeCategoryIds); })
                    && $this->arrayEvery($region["excludedCategoryIds"], function ($excludedCategoryId) use (&$placeCategoryIds) { return !in_array($excludedCategoryId, $placeCategoryIds); })) {
                    $placeCategoryIds[] = $region["categoryId"];
                }
            }

            $databaseProvider
                ->statementBuilder("DELETE FROM category WHERE place_id = ?")
                ->withParameters($placeIdentifierRow["id"])
                ->execute();

            foreach (array_unique($placeCategoryIds) as &$categoryId) {  
                $databaseProvider
                    ->statementBuilder("INSERT INTO category (place_id, category_id) VALUES (?, ?)")
                    ->withParameters($placeIdentifierRow["id"], $categoryId)
                    ->execute();

                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "CATEGORY", 
                        "id" => $categoryId), NULL);
            }
    
            $schedulingProvider
                ->scheduleJobExecution("UpdateRegionAreas", NULL, NULL);

            return TRUE;
        }

        public function getRequiredArguments() {
            return array("placeId");
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }

        private function getPointsOnCircle($x, $y, $radiusInKms, $numOfPoints) {
            $radius = $radiusInKms / 111;
    
            $result = array();
    
            for ($i = 0; $i < $numOfPoints; $i++) {
                $result[] = array($x + $this->positionX($numOfPoints, $i, $radius), $y + $this->positionY($numOfPoints, $i, $radius));
            }
    
            return $result;
        }
    
        private function positionX($numItems, $thisNum, $r) {
            $alpha = 360 / $numItems;
            $angle = $alpha * $thisNum;
            $x = $r * cos(deg2rad($angle));
            return $x;
        }
          
        private function positionY($numItems, $thisNum, $r) {
            $alpha = 360 / $numItems;
            $angle = $alpha * $thisNum;
            $y = $r * sin(deg2rad($angle));
            return $y;
        }

        private function arrayAny($array, $fn) {
            foreach ($array as &$value) {
                if ($fn($value)) {
                    return TRUE;
                }
            }
            return FALSE;
        }
    
        private function arrayEvery($array, $fn) {
            foreach ($array as &$value) {
                if (!$fn($value)) {
                    return FALSE;
                }
            }
            return TRUE;
        }
    }
?>