<?php
    require_once(dirname(__FILE__) . "/../model/TripIdentifier.php");
    require_once(dirname(__FILE__) . "/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");

    class ChangeTripIdentifierProcessor extends Processor {
        public function process($input) {
            global $databaseProvider, $schedulingProvider;

            if (isset($input["name"])) {
                $databaseProvider
                    ->statementBuilder("UPDATE trip_identifier SET name = ? WHERE id = ?")
                    ->withParameters($input["name"], $input["tripId"])
                    ->execute();                

                $tripsCalendarId = (new GetCalendarIdentifierProcessor())
                    ->process(array(
                        "name" => "trips"));

                $tripEventId = $databaseProvider
                    ->statementBuilder("SELECT id FROM trip_event WHERE trip_id = ?")
                    ->withParameters($input["tripId"])
                    ->getSingleColumn("id");

                if ($tripEventId != NULL) {
                    (new GetGoogleResponseProcessor())
                        ->process(array(
                            "method" => "PATCH", 
                            "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $tripsCalendarId . "/events/" . str_replace("@google.com", "", $tripEventId),
                            "payload" => json_encode(array(
                                "summary" => $input["name"]))));
                }
            }

            $schedulingProvider
                ->scheduleJobExecution("UpdateStats", array(
                    "type" => "TRIP", 
                    "id" => $input["tripId"]), NULL);    

            $tripRow = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_identifier WHERE id = ?")
                ->withParameters($input["tripId"])
                ->getSingleRow();

            return new TripIdentifier($tripRow["id"], $tripRow["name"], $tripRow["year"], $this->getHighlight($tripRow["main_highlight_id"]));
        }

        public function getRequiredArguments() {
            return array("tripId");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }

        private function getHighlight($highlightId) {
            global $databaseProvider;            
                
            $mainHighlightIdentifierRow = $databaseProvider
            ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.id = ?")
            ->withParameters($highlightId)
            ->getSingleRow();
            
           return $mainHighlightIdentifierRow == NULL ? NULL : new HighlightIdentifier($mainHighlightIdentifierRow["id"], $mainHighlightIdentifierRow["thumbnail_url"], $mainHighlightIdentifierRow["full_url"], 
                $mainHighlightIdentifierRow["focal_length"], $mainHighlightIdentifierRow["aperture"], $mainHighlightIdentifierRow["shutter_speed"], $mainHighlightIdentifierRow["iso"], $mainHighlightIdentifierRow["timestamp"]);
        }
    }
?>