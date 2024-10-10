<?php
    require_once(dirname(__FILE__) . "/../model/PlaceIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/HighlightIdentifier.php");
    require_once(dirname(__FILE__) . "/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/UpdateAlbumProcessor.php");

    class ChangePlaceIdentifierProcessor extends Processor {    
        public function process($input) {
            global $databaseProvider, $schedulingProvider;
            
            if (isset($input["mainHighlightId"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE place_identifier SET main_highlight_id = ? WHERE id = ? AND EXISTS(SELECT * FROM highlight_place WHERE highlight_id = ? AND id = ?)")
                    ->withParameters($input["mainHighlightId"], $input["placeId"], $input["mainHighlightId"], $input["placeId"])
                    ->execute();
            }

            if (isset($input["latitude"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE place_identifier SET latitude = ? WHERE id = ?")
                    ->withParameters($input["latitude"], $input["placeId"])
                    ->execute();
            }

            if (isset($input["longitude"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE place_identifier SET longitude = ? WHERE id = ?")
                    ->withParameters($input["longitude"], $input["placeId"])
                    ->execute();
            }

            if (isset($input["excerpt"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE place_identifier SET excerpt = ? WHERE id = ?")
                    ->withParameters($input["excerpt"], $input["placeId"])
                    ->execute();
            }

            if (isset($input["name"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE place_identifier SET name = ? WHERE id = ?")
                    ->withParameters($input["name"], $input["placeId"])
                    ->execute();
                
                $getGoogleResponseProcessor = new GetGoogleResponseProcessor();
                $updateAlbumProcessor = new UpdateAlbumProcessor();

                $placesCalendarId = (new GetCalendarIdentifierProcessor())
                    ->process(array(
                        "name" => "places"));

                $placeRows = $databaseProvider
                    ->statementBuilder("SELECT name, id, album_id FROM place_summary WHERE place_id = ?")
                    ->withParameters($input["placeId"])
                    ->getResultSet();

                foreach ($placeRows as &$placeRow) {
                    if ($placeRow["album_id"] != NULL) {
                        $albumName = $databaseProvider
                            ->statementBuilder("SELECT name FROM album WHERE id = ?")
                            ->withParameters($placeRow["album_id"])
                            ->getSingleColumn("name");

                        $getGoogleResponseProcessor
                            ->process(array(
                                "method" => "PATCH", 
                                "url" => "https://photoslibrary.googleapis.com/v1/albums/" . $placeRow["album_id"] . "?updateMask=title", 
                                "payload" => json_encode(array(
                                    "title" => str_replace($placeRow["name"], $input["name"], $albumName)))));
                        
                        $updateAlbumProcessor
                            ->process(array(
                                "albumId" => $placeRow["album_id"]));
                    }
                    
                    if ($placeRow["id"] != NULL) {    
                        $getGoogleResponseProcessor
                            ->process(array(
                                "method" => "PATCH", 
                                "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $placesCalendarId . "/events/" . str_replace("@google.com", "", $placeRow["id"]),
                                "payload" => json_encode(array(
                                    "summary" => $input["name"]))));
                    }
                }
            }

            $tripIds = $databaseProvider
                ->statementBuilder("SELECT DISTINCT trip_id FROM place_summary WHERE place_id = ? AND trip_id IS NOT NULL")
                ->withParameters($input["placeId"])
                ->getResultSetForColumn("trip_id");

            foreach ($tripIds as &$tripId) {
                $schedulingProvider
                    ->scheduleJobExecution("UpdateStats", array(
                        "type" => "TRIP", 
                        "id" => $tripId), NULL); 
            }   

            $placeIdentifierRow = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($input["placeId"])
                ->getSingleRow();
            
            return new PlaceIdentifier($placeIdentifierRow["id"], $placeIdentifierRow["name"], $placeIdentifierRow["country"], 
                $placeIdentifierRow["latitude"], $placeIdentifierRow["longitude"], $placeIdentifierRow["timezone"], $this->getHighlight($placeIdentifierRow["main_highlight_id"]));
        }

        public function getRequiredArguments() {
            return array("placeId");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }

        private function getHighlight($highlightId) {
            global $databaseProvider;            
                
            $highlightRow = $databaseProvider
                ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.id = ?")
                ->withParameters($highlightId)
                ->getSingleRow();
            
           return $highlightRow == NULL ? NULL : new HighlightIdentifier($highlightRow["id"], $highlightRow["thumbnail_url"], $highlightRow["full_url"], 
                $highlightRow["focal_length"], $highlightRow["aperture"], $highlightRow["shutter_speed"], $highlightRow["iso"], $highlightRow["timestamp"]);
        }
    }
?> 