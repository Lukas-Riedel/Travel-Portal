<?php
    require_once(dirname(__FILE__) . "/GetCategoryIdentifierProcessor.php");

    class AddGeographicalRegionExtensionProcessor extends Processor {    
        public function process($input) {
            global $databaseProvider, $schedulingProvider;
            
            $country = isset($input["country"]) ? $input["country"] : NULL;
            $geoJson = json_encode(array(
                "type" => "Feature", 
                "geometry" => array(
                    "type" => "Point", 
                    "coordinates" => array(
                        floatval($input["longitude"]), 
                        floatval($input["latitude"])))), true);
            
            $categoryIdentifier = (new GetCategoryIdentifierProcessor())
                ->process(array(
                    "name" => $input["name"],
                    "category" => $input["category"]));

            $databaseProvider
                ->statementBuilder("INSERT INTO region_geographical (category_id, country, json, radius) VALUES (?, ?, ?, 0)")
                ->withParameters($categoryIdentifier->getId(), $country, $geoJson)
                ->execute();
    
            $placeIdsToUpdate = $databaseProvider
                ->statementBuilder("SELECT id FROM place_identifier WHERE latitude = ? AND longitude = ?")
                ->withParameters($input["latitude"], $input["longitude"])
                ->getResultSetForColumn("id");

            foreach ($placeIdsToUpdate as &$placeIdToUpdate) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateCategories", array(
                        "placeId" => $placeIdToUpdate), NULL);
            }

            return $categoryIdentifier;
        }

        public function getRequiredArguments() {
            return array("name", "category", "latitude", "longitude");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }
    }
?>