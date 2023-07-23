<?php
    class GetGeographicalRegionsProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            
            $geoJson = array(
                "type" => "FeatureCollection", 
                "features" => array());

            $geoRegions = $databaseProvider
                ->statementBuilder("SELECT ci.name, rg.json FROM region_geographical rg INNER JOIN category_identifier ci ON rg.category_id = ci.id")
                ->getResultSet();

            foreach ($geoRegions as &$geoRegion) {
                $geoJson["features"][] = array_merge(
                    json_decode($geoRegion["json"], true), 
                    array(
                        "properties" => array(
                            "name" => $geoRegion["name"])));
            }

            return $geoJson;
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }
    }
?>