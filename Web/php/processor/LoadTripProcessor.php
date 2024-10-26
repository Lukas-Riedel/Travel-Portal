<?php
    require_once(dirname(__FILE__) . "/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/../model/Trip.php");

    class LoadTripProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider;

            $targetTripRow = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_summary WHERE trip_id = ?")
                ->withParameters($input["tripId"])
                ->getSingleRow();

            if ($targetTripRow == NULL) {
                throw new InvalidArgumentException("The target trip " . $input["tripId"] . " was not found.");
            }

            $placeRows = $databaseProvider
                ->statementBuilder("SELECT pi.name, pi.country, pi.latitude, pi.longitude, pce.start, pce.end FROM place_candidate_event pce INNER JOIN place_identifier pi ON pce.place_id = pi.id WHERE pce.trip_id = ? ORDER BY pce.start ASC")
                ->withParameters($input["candidateTripId"])
                ->getResultSet();

            foreach ($placeRows as &$placeRow) {
                $address = $placeRow["name"] . ", " . $placeRow["country"] . " (" . $placeRow["latitude"] . ", " . $placeRow["longitude"] . ")";
                $this->createPlaceEvent($placeRow["name"], $address, $targetTripRow["start"] + $placeRow["start"], $targetTripRow["start"] + $placeRow["end"]);
            }

            $databaseProvider
                ->statementBuilder("DELETE FROM place_candidate_event WHERE trip_id = ?")
                ->withParameters($input["candidateTripId"])
                ->execute();

            $databaseProvider
                ->statementBuilder("UPDATE note SET trip_id = ? WHERE trip_id = ?")
                ->withParameters($input["tripId"], $input["candidateTripId"])
                ->execute();
            
            // Some fields are omitted, supply values if needed.
            return new Trip($targetTripRow["trip_id"], $targetTripRow["name"], $targetTripRow["year"], $this->getHighlight($targetTripRow["main_highlight_id"]), $targetTripRow["start"], $targetTripRow["end"], array(),
                $targetTripRow["cost"], $targetTripRow["days"], isset($targetTripRow["working_days"]) ? $targetTripRow["working_days"] : NULL, 
                isset($targetTripRow["expected_vacation"]) ? $targetTripRow["expected_vacation"] : NULL, isset($targetTripRow["max_vacation"]) ? $targetTripRow["max_vacation"] : NULL,
                array(), array(), array(), array(), array(), array(), array(), array(), array(), array());
        }

        public function getRequiredArguments() {
            return array("tripId", "candidateTripId");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }

        private function createPlaceEvent($name, $address, $start, $end) {
            global $configuration;
            
            $payload = array(
                "summary" => $name, 
                "start" => array(
                    "dateTime" => date(DATE_RFC3339, $start),
                    "timeZone" => $configuration["homeLocation"]["timezone"]), 
                "end" => array(
                    "dateTime" => date(DATE_RFC3339, $end),
                    "timeZone" => $configuration["homeLocation"]["timezone"]),
                "location" => $address);

            $calendarId = (new GetCalendarIdentifierProcessor())
                ->process(array(
                    "name" => "places"));
                    
            return (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST",
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events", 
                    "payload" => json_encode($payload)));
        }

        private function getHighlight($highlightId) {
            global $databaseProvider;            
                
            $mainHighlightIdentifierRow = $databaseProvider
            ->statementBuilder("SELECT hi.*, p.focal_length, p.aperture, p.shutter_speed, p.iso, p.timestamp FROM highlight_identifier hi LEFT JOIN photo p ON hi.photo_id = p.id WHERE hi.id = ?")
            ->withParameters($highlightId)
            ->getSingleRow();
            
           return $mainHighlightIdentifierRow == NULL ? NULL : new Highlight($mainHighlightIdentifierRow["id"], $mainHighlightIdentifierRow["thumbnail_url"], $mainHighlightIdentifierRow["full_url"], 
                $mainHighlightIdentifierRow["focal_length"], $mainHighlightIdentifierRow["aperture"], $mainHighlightIdentifierRow["shutter_speed"], $mainHighlightIdentifierRow["iso"], $mainHighlightIdentifierRow["timestamp"]);
        }
    }
?>