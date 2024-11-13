<?php
    require_once(dirname(__FILE__) . "/../model/Album.php");
    require_once(dirname(__FILE__) . "/../model/Date.php");
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/TripIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Highlight.php");
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
            if (isset($input["year"])) {
                $whereClauseBuilder->withClause("DATE_FORMAT(FROM_UNIXTIME(start), '%Y') = ?", $input["year"]);
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
            
            $placeRows = $databaseProvider
                ->statementBuilder("SELECT * FROM place_summary {{WHERE CLAUSE}} ORDER BY start", $whereClause)
                ->getResultSet();

            foreach ($placeRows as &$placeRow) {
                if (!isset($tempPlaces[$placeRow["place_id"]])) {    
                    $categories = array();  
                    $highlights = array();                          
                    
                    $includeCategories = isset($input["includeCategories"]) && $input["includeCategories"] == "true";
                    if ($includeCategories || isset($input["placeId"])) {
                        foreach (explode(",", $placeRow["category_ids"]) as &$categoryId) {
                            $categoryRow = $databaseProvider
                                ->statementBuilder("SELECT * FROM category_identifier WHERE id = ?")
                                ->withParameters($categoryId)
                                ->getSingleRow();
    
                            if ($categoryRow != NULL) {
                                $categories[] = new CategoryIdentifier($categoryRow["id"], $categoryRow["name"], $categoryRow["category"], $this->getHighlight($categoryRow["main_highlight_id"]));
                            }
                        }
                    }                   

                    $includeHighlights = isset($input["includeHighlights"]) && $input["includeHighlights"] == "true";
                    if ($includeHighlights || isset($input["placeId"])) {
                        $highlights = $this->getHighlights($placeRow);                      
                    }
                    
                    $includeExcerpt = isset($input["includeExcerpt"]) && $input["includeExcerpt"] == "true";
                    $tempPlaces[$placeRow["place_id"]] = new Place($placeRow["place_id"], $placeRow["name"], $placeRow["country"], $placeRow["latitude"], $placeRow["longitude"], $placeRow["timezone"],
                        $this->getHighlight($placeRow["main_highlight_id"]), ($includeExcerpt || isset($input["placeId"])) ? $placeRow["excerpt"] : NULL, $categories, $highlights, array());
                }
                
                $weather = NULL;
                if ($placeRow["end"] > time()) {
                    if ($placeRow["temperature"] !== NULL && $placeRow["wind"] !== NULL && $placeRow["precipitation"] !== NULL && $placeRow["sunrise"] !== NULL && $placeRow["sunset"] !== NULL && $placeRow["start_sun_altitude"] !== NULL && $placeRow["end_sun_altitude"] !== NULL && $placeRow["start_sun_azimuth"] !== NULL && $placeRow["end_sun_azimuth"] !== NULL) {
                        $weather = new Weather($placeRow["temperature"], $placeRow["clouds"], $placeRow["wind"], $placeRow["precipitation"], $placeRow["symbol"], $placeRow["sunrise"], $placeRow["sunset"], $placeRow["start_sun_altitude"], $placeRow["end_sun_altitude"], $placeRow["start_sun_azimuth"], $placeRow["end_sun_azimuth"], $placeRow["last_update"]);
                    }
                }

                $album = NULL;
                if ($placeRow["album_id"] != NULL) {                    
                    $album = new Album($placeRow["album_id"], $placeRow["name"] . " " . date("j.n.Y", $placeRow["start"]), $placeRow["album_main_photo_id"], $placeRow["album_main_image_url"], $placeRow["album_permalink"], $placeRow["album_images_count"], $placeRow["album_indoor_images_count"]);
                }

                $trip = NULL;
                if ($placeRow["trip_id"] != NULL) {
                    $tripRow = $databaseProvider
                        ->statementBuilder("SELECT * FROM trip_identifier WHERE id = ?")
                        ->withParameters($placeRow["trip_id"])
                        ->getSingleRow();

                    $trip = new TripIdentifier($tripRow["id"], $tripRow["name"], $tripRow["year"], $this->getHighlight($tripRow["main_highlight_id"]));
                }
                
                $tempPlaces[$placeRow["place_id"]]->addDate(new Date($placeRow["start"], $placeRow["end"], $weather, $album, $trip));  
            }

            return array_values($tempPlaces);
        }

        public function getRequiredArguments() {
            return array();
        }
        
        public function requiresAdminRole() {
            return FALSE;
        }

        private function getRelevantImagesCountForScore($album) {
            return $album->getImagesCount() == 0 || $album->getIndoorImagesCount() / $album->getImagesCount() > 0.7
                ? $album->getImagesCount() // This is an indoor-only location.
                : $album->getImagesCount() - $album->getIndoorImagesCount(); // Exclude indoor photos from the score.
        }

        private function getHighlights($placeRow) {
            global $databaseProvider;

            return $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_place hp INNER JOIN highlight_identifier hi ON hp.highlight_id = hi.id LEFT JOIN photo p ON hi.photo_id = p.id WHERE hp.id = ?")
                ->withParameters($placeRow["place_id"])
                ->getMappedResultSet(function ($highlightRow) { 
                    return new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], $highlightRow["focal_length"], 
                        $highlightRow["aperture"], $highlightRow["shutter_speed"], $highlightRow["iso"], $highlightRow["timestamp"]);
                });
        }

        private function getHighlight($highlightId) {
            global $databaseProvider;            
                
            $highlightRow = $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.id = ?")
                ->withParameters($highlightId)
                ->getSingleRow();
            
           return $highlightRow == NULL ? NULL : new Highlight($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], 
                $highlightRow["focal_length"], $highlightRow["aperture"], $highlightRow["shutter_speed"], $highlightRow["iso"], $highlightRow["timestamp"]);
        }
    }
?>