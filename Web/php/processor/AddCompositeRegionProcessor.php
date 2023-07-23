<?php
    require_once(dirname(__FILE__) . "/GetCategoryIdentifierProcessor.php");

    class AddCompositeRegionProcessor extends Processor {    
        public function process($input) {
            global $databaseProvider, $schedulingProvider;

            $referencableRegionNames = $databaseProvider
                ->statementBuilder("SELECT name FROM category_identifier")
                ->getResultSetForColumn("name");
            
            foreach ($configuration["countries"] as $whatever => $countryConfigurationValue) {
                if (!in_array($countryConfigurationValue["name"], $referencableRegionNames)) {
                    $referencableRegionNames[] = $countryConfigurationValue["name"];
                }
            }

            foreach ($input["includedRegions"] as &$includedRegion) {
                if (!in_array($includedRegion, $referencableRegionNames)) {
                    throw new InvalidArgumentException("A referenced region with the identifier " . $includedRegion . " does not exist.");
                }
            }

            foreach ($input["excludedRegions"] as &$excludedRegion) {
                if (!in_array($excludedRegion, $referencableRegionNames)) {
                    throw new InvalidArgumentException("A referenced region with the identifier " . $excludedRegion . " does not exist.");
                }
            }

            $getCategoryIdentifierProcessor = new GetCategoryIdentifierProcessor();
            $categoryIdentifier = $getCategoryIdentifierProcessor
                ->process(array(
                    "name" => $input["name"],
                    "category" => $input["category"]));
            
            $databaseProvider
                ->statementBuilder("DELETE FROM region_composite WHERE category_id = ?")
                ->withParameters($categoryIdentifier->getId())
                ->execute();

            foreach ($input["includedRegions"] as &$includedRegion) {
                $subjectCategoryIdentifier = $getCategoryIdentifierProcessor
                    ->process(array(
                        "name" => $includedRegion));

                $databaseProvider
                    ->statementBuilder("INSERT INTO region_composite (category_id, subject_category_id, type) VALUES (?, ?, 'INCLUDE')")
                    ->withParameters($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId())
                    ->execute();
            }

            foreach ($input["excludedRegions"] as &$excludedRegion) {
                $subjectCategoryIdentifier = $getCategoryIdentifierProcessor
                    ->process(array(
                        "name" => $excludedRegion));

                $databaseProvider
                    ->statementBuilder("INSERT INTO region_composite (category_id, subject_category_id, type) VALUES (?, ?, 'EXCLUDE')")
                    ->withParameters($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId())
                    ->execute();
            }

            $placeIdsToUpdate = $databaseProvider
                ->statementBuilder("SELECT id FROM place_identifier")
                ->getResultSetForColumn("id");

            foreach ($placeIdsToUpdate as &$placeIdToUpdate) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateCategories", array(
                        "placeId" => $placeIdToUpdate), NULL);
            }
            
            return $categoryIdentifier;
        }

        public function getRequiredArguments() {
            return array("name", "category", "includedRegions", "excludedRegions");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>