<?php    
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/../model/Trip.php");

    class MoveTripProcessor extends Processor {
        public function process($input) {
            global $databaseProvider;
            
            $tripRow = $databaseProvider
                ->statementBuilder("SELECT * FROM trip_summary WHERE trip_id = ?")
                ->withParameters($input["tripId"])
                ->getSingleRow();

            if ($tripRow == NULL) {
                throw new InvalidArgumentException("The trip " . $input["tripId"] . " was not found.");
            }

            $offset = $input["start"] - $tripRow["start"];
            $this->updateEventStartAndEnd("trips", $tripRow["id"], $tripRow["start"] + $offset, $tripRow["end"] + $offset);

            $placeRows = $databaseProvider
                ->statementBuilder("SELECT pe.id, pe.start, pe.end, pi.timezone FROM place_event pe INNER JOIN place_identifier pi ON pe.place_id = pi.id WHERE pe.trip_id = ?")
                ->withParameters($input["tripId"])
                ->getResultSet();

            foreach ($placeRows as &$placeRow) {
                $timezoneOffset = $this->getTimezoneOffset($placeRow["start"], $placeRow["timezone"]);
                $this->updateEventStartAndEnd("places", $placeRow["id"], $placeRow["start"] + $timezoneOffset + $offset, $placeRow["end"] + $timezoneOffset + $offset);
            }

            // Some fields are omitted, supply values if needed.
            // Vacation data may be inaccurate until the next calendar update.
            return new Trip($tripRow["trip_id"], $tripRow["name"], $tripRow["year"], $this->getHighlight($tripRow["main_highlight_id"]), $tripRow["start"] + $offset, $tripRow["end"] + $offset, array(),
                $tripRow["cost"], $tripRow["days"], isset($tripRow["working_days"]) ? $tripRow["working_days"] : NULL, 
                isset($tripRow["expected_vacation"]) ? $tripRow["expected_vacation"] : NULL, isset($tripRow["max_vacation"]) ? $tripRow["max_vacation"] : NULL,
                array(), array(), array(), array(), array(), array(), array(), array(), array());
        }

        public function getRequiredArguments() {
            return array("tripId", "start");
        }
        
        public function requiresAuthentication() {
            return TRUE;
        }

        private function updateEventStartAndEnd($calendar, $event, $newStart, $newEnd) {
            global $configuration;

            $eventId = explode("@", $event)[0];
            $calendarId = (new GetCalendarIdentifierProcessor())
                ->process(array(
                    "name" => $calendar));
                    
            $apiResponse = (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "GET",
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events/" . $eventId));

            $apiResponse["start"] = array(
                "dateTime" => date(DATE_RFC3339, $newStart),
                "timeZone" => $configuration["homeLocation"]["timezone"]);

            $apiResponse["end"] = array(
                "dateTime" => date(DATE_RFC3339, $newEnd),
                "timeZone" => $configuration["homeLocation"]["timezone"]);

            return (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "PUT",
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events/" . $eventId, 
                    "payload" => json_encode($apiResponse)));
        }    

        private function getTimezoneOffset($timestamp, $fromTimezone) {
            global $configuration;

            $timezone = new DateTimeZone($fromTimezone);
            $dateTimeAtHome = new DateTime("now", new DateTimeZone($configuration["homeLocation"]["timezone"]));
            $dateTimeAtHome->setTimestamp($timestamp);
            return $timezone->getOffset($dateTimeAtHome) - (new DateTimeZone($configuration["homeLocation"]["timezone"]))->getOffset($dateTimeAtHome);
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