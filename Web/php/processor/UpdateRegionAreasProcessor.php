<?php
    require_once(dirname(__FILE__) . "/../GeoPHP/geoPHP.inc");

    class UpdateRegionAreasProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $configuration;
            
            $areas = array();

            $geoRegionRows = $databaseProvider
                ->statementBuilder("SELECT * FROM region_geographical WHERE json NOT LIKE '%Point%'")
                ->getResultSet();

            foreach ($geoRegionRows as &$geoRegionRow) {
                $area = geoPHP::load($geoRegionRow["json"], "json")->getArea();
                $areas[$geoRegionRow["category_id"]] = $area;

                if ($geoRegionRow["country"] != NULL) {
                    $countryCategoryId = $databaseProvider
                        ->statementBuilder("SELECT id FROM category_identifier WHERE name = ?")
                        ->withParameters($geoRegionRow["country"])
                        ->getSingleColumn("id");

                    if ($countryCategoryId != NULL) {
                        if (!array_key_exists($countryCategoryId, $areas)) {
                            $areas[$countryCategoryId] = 0;
                        }
                        $areas[$countryCategoryId] += $area;
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
                    if ($compositeRegionRow["type"] == "INCLUDE") {
                        $areas[$compositeRegionRow["category_id"]] += $areas[$compositeRegionRow["subject_category_id"]];
                    }
                    else if ($compositeRegionRow["type"] == "EXCLUDE") {
                        $areas[$compositeRegionRow["category_id"]] -= $areas[$compositeRegionRow["subject_category_id"]];
                    }
                }

                $databaseProvider
                    ->statementBuilder("DELETE FROM region_area WHERE category_id = ?")
                    ->withParameters($compositeRegionRow["category_id"])
                    ->execute();
                $databaseProvider
                    ->statementBuilder("INSERT INTO region_area (category_id, area) VALUES (?, ?)")
                    ->withParameters($compositeRegionRow["category_id"], $area)
                    ->execute();
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

            return TRUE;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }
    }
?>