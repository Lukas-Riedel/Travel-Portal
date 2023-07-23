<?php
    require_once(dirname(__FILE__) . "/../model/Album.php");
    require_once(dirname(__FILE__) . "/../model/Date.php");
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/TripIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Weather.php");
    require_once(dirname(__FILE__) . "/../model/Place.php");

    class GetPlacesProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $permanentPlaceIdentifiers = $databaseProvider
                ->statementBuilder("SELECT place_id FROM place_permanent")
                ->getResultSetForColumn("place_id");
            
            $tempPlaces = array();

            $whereClauseBuilder = $databaseProvider->whereClauseBuilder();
            if (isset($input["placeId"])) {
                $whereClauseBuilder->withClause("place_id = ?", $input["placeId"]);
            }
            if (isset($input["yearId"])) {
                $whereClauseBuilder->withClause("DATE_FORMAT(FROM_UNIXTIME(start), '%Y') = ?", $input["yearId"]);
            }
            if (isset($input["tripId"])) {
                $whereClauseBuilder->withClause("trip_id = ?", $input["tripId"]);
            }
            if (isset($input["categoryId"])) {
                $whereClauseBuilder->withClause("(FIND_IN_SET(?, category_ids) OR ((UNIX_TIMESTAMP() - GET_VARIABLE_TIME_CATEGORY_OFFSET(?) <= start) AND (UNIX_TIMESTAMP() >= end)) OR ((GET_VARIABLE_TIME_CATEGORY_OFFSET(?) IS NOT NULL) AND (place_id IN (SELECT place_id FROM place_permanent))))", $input["categoryId"], $input["categoryId"], $input["categoryId"]);
            }
            if (isset($input["minStart"])) {
                $whereClauseBuilder->withClause("? <= start", $input["minStart"]);
            }
            if (isset($input["maxEnd"])) {
                $whereClauseBuilder->withClause("end <= ?", $input["maxEnd"]);
            }
            $whereClause = $whereClauseBuilder->buildForAnd();
            
            $places = $databaseProvider
                ->statementBuilder("SELECT * FROM place_summary {{WHERE CLAUSE}} ORDER BY start", $whereClause)
                ->getResultSet();

            foreach ($places as &$place) {
                if (!isset($tempPlaces[$place["place_id"]])) {    
                    $categories = array();        
                    
                    foreach (explode(",", $place["category_ids"]) as &$categoryId) {
                        $categoryRow = $databaseProvider
                            ->statementBuilder("SELECT id, name, category FROM category_identifier WHERE id = ?")
                            ->withParameters($categoryId)
                            ->getSingleRow();

                        if ($categoryRow != NULL) {
                            $categories[] = new CategoryIdentifier($categoryRow["id"], $categoryRow["name"], $categoryRow["category"]);
                        }
                    }

                    $tempPlaces[$place["place_id"]] = new Place($place["place_id"], $place["name"], $place["country"], $place["latitude"], $place["longitude"], $place["timezone"], $categories, array());
                }
                
                $weather = NULL;
                if ($place["end"] > time()) {
                    if ($place["temperature"] !== NULL && $place["wind"] !== NULL && $place["precipitation"] !== NULL && $place["sunrise"] !== NULL && $place["sunset"] !== NULL) {
                        $weather = new Weather($place["temperature"], $place["clouds"], $place["wind"], $place["precipitation"], $place["symbol"], $place["sunrise"], $place["sunset"], $place["last_update"]);
                    }
                }

                $album = NULL;
                if ($place["album_id"] != NULL) {                    
                    $album = new Album($place["album_id"], $place["name"] . " " . date("j.n.Y", $place["start"]), $place["album_main_image_url"], $place["album_permalink"], $place["album_images_count"], $place["album_indoor_images_count"], $place["album_images_count"] == 0, 
                        $place["is_main_album_for_place"] == 1, $place["is_main_album_for_country"] == 1, $place["is_main_album_for_trip"] == 1, $place["is_low_quality_album"] == 1, $place["is_bad_weather_album"] == 1);
                }

                $trip = NULL;
                if ($place["trip_id"] != NULL) {
                    $tripRow = $databaseProvider
                        ->statementBuilder("SELECT * FROM trip_identifier WHERE id = ?")
                        ->withParameters($place["trip_id"])
                        ->getSingleRow();

                    $trip = new TripIdentifier($tripRow["id"], $tripRow["name"], $tripRow["year"]);
                }
                
                $tempPlaces[$place["place_id"]]->addDate(new Date($place["start"], $place["end"], $weather, $album, $trip));  
            }

            return array_values($tempPlaces);
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAuthentication() {
            return FALSE;
        }

        private function getRelevantImagesCountForScore($album) {
            return $album->getImagesCount() == 0 || $album->getIndoorImagesCount() / $album->getImagesCount() > 0.7
                ? $album->getImagesCount() // This is an indoor-only location.
                : $album->getImagesCount() - $album->getIndoorImagesCount(); // Exclude indoor photos from the score.
        }
    }
?>