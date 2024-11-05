<?php
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");

    class CategoryService {
        public function getCategoryIdentifier($name) : ?CategoryIdentifier {
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
        
        public function getOrCreateCategoryIdentifier($name, $category) : CategoryIdentifier { 
            global $databaseProvider;

            $categoryIdentifier = $this->getCategoryIdentifier($name);
            if ($categoryIdentifier !== NULL) {
                return $categoryIdentifier;
            }

            $databaseProvider
                ->statementBuilder("INSERT INTO category_identifier (name, category) VALUES (?, ?)")
                ->withParameters($name, $category)
                ->execute();
                
            return $this->getCategoryIdentifier($name);
        }

        public function createCompositeRegion($name, $category, $includedRegions, $excludedRegions) : CategoryIdentifier {
            global $databaseProvider, $schedulingProvider, $configuration, $placeService;

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
                    throw new InvalidArgumentException("The included region " . $includedRegion . " does not exist.");
                }
            }

            foreach ($excludedRegions as &$excludedRegion) {
                if (!in_array($excludedRegion, $referencableRegionNames)) {
                    throw new InvalidArgumentException("The excluded region " . $excludedRegion . " does not exist.");
                }
            }

            // Create the region.
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category);    

            $databaseProvider
                ->statementBuilder("DELETE FROM region_composite WHERE category_id = ?")
                ->withParameters($categoryIdentifier->getId())
                ->execute();

            foreach ($includedRegions as &$includedRegion) {
                $subjectCategoryIdentifier = $this->getCategoryIdentifier($includedRegion);
                $databaseProvider
                    ->statementBuilder("INSERT INTO region_composite (category_id, subject_category_id, type) VALUES (?, ?, 'INCLUDE')")
                    ->withParameters($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId())
                    ->execute();
            }

            foreach ($excludedRegions as &$excludedRegion) {
                $subjectCategoryIdentifier = $this->getCategoryIdentifier($excludedRegion);
                $databaseProvider
                    ->statementBuilder("INSERT INTO region_composite (category_id, subject_category_id, type) VALUES (?, ?, 'EXCLUDE')")
                    ->withParameters($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId())
                    ->execute();
            }

            $placeIdentifiers = $placeService->getAllPlaceIdentifiers();

            foreach ($placeIdentifiers as &$placeIdentifier) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateCategories", array(
                        "placeId" => $placeIdentifier->getId()), NULL);
            }
            
            return $categoryIdentifier;
        }

        public function createGeographicalRegion($name, $country, $category, $radius, $geoJson) : CategoryIdentifier {
            global $databaseProvider, $schedulingProvider, $placeService;
                                    
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
                $schedulingProvider
                    ->scheduleJobExecution("UpdateCategories", array(
                        "placeId" => $placeIdentifier->getId()), NULL);
            }
            
            return $categoryIdentifier;
        }

        public function createGeographicalRegionExtensionRegion($name, $country, $category, $latitude, $longitude) : CategoryIdentifier {
            global $databaseProvider, $schedulingProvider, $placeService;
            
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
                $schedulingProvider
                    ->scheduleJobExecution("UpdateCategories", array(
                        "placeId" => $placeIdentifier->getId()), NULL);
            }
            
            return $categoryIdentifier;
        }
    }
?>