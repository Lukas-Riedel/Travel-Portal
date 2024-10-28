<?php
    class AddGeographicalRegionProcessor extends Processor {    
        public function process($input) {
            global $databaseProvider, $schedulingProvider, $categoryService;
            
            $country = isset($input["country"]) ? $input["country"] : NULL;
                        
            $categoryIdentifier = $categoryService->getOrCreateCategoryIdentifier($input["name"], $input["category"]); 

            $databaseProvider
                ->statementBuilder("DELETE FROM region_geographical WHERE category_id = ? AND country " . $databaseProvider->getIsNullOrEqualTo($country))
                ->withParameters($categoryIdentifier->getId())
                ->execute();
                
            $databaseProvider
                ->statementBuilder("INSERT INTO region_geographical (category_id, country, json, radius) VALUES (?, ?, ?, ?)")
                ->withParameters($categoryIdentifier->getId(), $country, $input["geoJson"], $input["radius"])
                ->execute();

            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if (isset($input["country"])) {
                $whereClauseBuilder->withClause("country = ?", $input["country"]);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();

            $placeIdsToUpdate = $databaseProvider
                ->statementBuilder("SELECT id FROM place_identifier {{WHERE CLAUSE}}", $whereClause)
                ->getResultSetForColumn("id");

            foreach ($placeIdsToUpdate as &$placeIdToUpdate) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateCategories", array(
                        "placeId" => $placeIdToUpdate), NULL);
            }

            return $categoryIdentifier;
        }

        public function getRequiredArguments() {
            return array("name", "category", "radius", "geoJson");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>