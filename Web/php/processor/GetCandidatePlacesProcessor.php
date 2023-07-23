<?php
    require_once(dirname(__FILE__) . "/../model/Album.php");
    require_once(dirname(__FILE__) . "/../model/Date.php");
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/TripIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Place.php");
    require_once(dirname(__FILE__) . "/GetTripIdentifierProcessor.php");

    class GetCandidatePlacesProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;
            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
 
           if (isset($input["categoryId"])) {
                $whereClauseBuilder->withClause("FIND_IN_SET(?, category_ids)", $input["categoryId"]);
            }
            if (isset($input["placeId"])) {
                $whereClauseBuilder->withClause("cs.place_id = ?", $input["placeId"]);
            }

            return isset($input["tripId"])
                ? $this->getCandidatePlacesForTrip($input, $whereClauseBuilder)
                : $this->getCandidatePlaces($input, $whereClauseBuilder);
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }

        private function getCandidatePlaces($input, $whereClauseBuilder) {
            global $databaseProvider;

            $whereClause = $whereClauseBuilder->buildForAnd();

            $placeRows = $databaseProvider
                ->statementBuilder("SELECT pcan.*, cs.category_ids FROM (SELECT place_id, name, country, latitude, longitude, timezone FROM place_candidate pc INNER JOIN place_identifier pi ON pc.place_id = pi.id UNION SELECT place_id, name, country, latitude, longitude, timezone FROM place_candidate_event pce INNER JOIN place_identifier pi ON pce.place_id = pi.id UNION SELECT ps.place_id, ps.name, ps.country, ps.latitude, ps.longitude, ps.timezone FROM place_event p INNER JOIN place_summary ps ON p.place_id = ps.place_id WHERE ps.start < UNIX_TIMESTAMP() GROUP BY ps.name, ps.country HAVING (MAX(ps.start) < UNIX_TIMESTAMP() - GET_CONFIGURATION('DAYS_BEFORE_APPEARING_IN_PLAN') * 86400) OR MAX(ps.album_id) IS NULL OR (MAX(ps.is_low_quality_album) = 1 AND MIN(ps.is_low_quality_album) = 1) OR (MAX(ps.is_bad_weather_album) = 1 AND MIN(ps.is_bad_weather_album) = 1)) pcan INNER JOIN category_summary cs ON pcan.place_id = cs.place_id {{WHERE CLAUSE}} ORDER BY country, name", $whereClause)
                ->getResultSet();

            $result = array();

            foreach ($placeRows as &$placeRow) {
                $dateRows = $databaseProvider
                    ->statementBuilder("SELECT * FROM place_summary WHERE place_id = ? AND start < UNIX_TIMESTAMP()")
                    ->withParameters($placeRow["place_id"])
                    ->getResultSet();
                    
                $dates = array();

                foreach ($dateRows as &$dateRow) {    
                    $album = NULL;
                    if ($dateRow["album_id"] != NULL) {                    
                        $album = new Album($dateRow["album_id"], $dateRow["name"], $dateRow["album_main_image_url"], $dateRow["album_permalink"], $dateRow["album_images_count"], $dateRow["album_indoor_images_count"], $dateRow["album_images_count"] == 0, 
                            $dateRow["is_main_album_for_place"] == 1, $dateRow["is_main_album_for_country"] == 1, $dateRow["is_main_album_for_trip"] == 1, $dateRow["is_low_quality_album"] == 1, $dateRow["is_bad_weather_album"] == 1);
                    }
    
                    $trip = NULL;
                    if ($dateRow["trip_id"] != NULL) {
                        $tripRow = $databaseProvider
                            ->statementBuilder("SELECT * FROM trip_identifier WHERE id = ?")
                            ->withParameters($dateRow["trip_id"])
                            ->getSingleRow();
    
                        $trip = new TripIdentifier($tripRow["id"], $tripRow["name"], $tripRow["year"]);
                    }

                    $dates[] = new Date($dateRow["start"], $dateRow["end"], NULL, $album, $trip);
                }                

                $result[] = new Place($placeRow["place_id"], $placeRow["name"], $placeRow["country"], $placeRow["latitude"], $placeRow["longitude"], $placeRow["timezone"], $this->getCategories(explode(",", $placeRow["category_ids"])), $dates);                
            }
            
            return $result;
        }

        private function getCandidatePlacesForTrip($input, $whereClauseBuilder) {
            global $databaseProvider;

            $whereClause = $whereClauseBuilder->withClause("trip_id = ?", $input["tripId"])->buildForAnd();

            $placeRows = $databaseProvider
                ->statementBuilder("SELECT pce.place_id, pi.name, pi.country, pi.latitude, pi.longitude, pi.timezone, pce.start, pce.end, cs.category_ids FROM place_candidate_event pce INNER JOIN place_identifier pi ON pce.place_id = pi.id INNER JOIN category_summary cs ON pi.id = cs.place_id {{WHERE CLAUSE}}", $whereClause)
                ->getResultSet();

            $tempPlaces = array();

            foreach ($placeRows as &$placeRow) {
                if (!isset($tempPlaces[$placeRow["place_id"]])) {            
                    $tempPlaces[$placeRow["place_id"]] = new Place($placeRow["place_id"], $placeRow["name"], $placeRow["country"], $placeRow["latitude"], $placeRow["longitude"], $placeRow["timezone"], $this->getCategories(explode(",", $placeRow["category_ids"])), array()); 
                }
                
                $tripRow = $databaseProvider
                    ->statementBuilder("SELECT * FROM trip_identifier WHERE id = ?")
                    ->withParameters($input["tripId"])
                    ->getSingleRow();

                $trip = new TripIdentifier($tripRow["id"], $tripRow["name"], $tripRow["year"]);
                
                $tempPlaces[$placeRow["place_id"]]->addDate(new Date($placeRow["start"], $placeRow["end"], NULL, NULL, $trip));
            }

            return array_values($tempPlaces);
        }

        private function getCategories($categoryIds) {
            global $databaseProvider;

            $categories = array();     

            foreach ($categoryIds as &$categoryId) {
                $categoryRow = $databaseProvider
                    ->statementBuilder("SELECT id, name, category FROM category_identifier WHERE id = ?")
                    ->withParameters($categoryId)
                    ->getSingleRow();
                $categories[] = new CategoryIdentifier($categoryRow["id"], $categoryRow["name"], $categoryRow["category"]);
            }

            return $categories;
        }
    }
?>