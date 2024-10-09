<?php
    require_once(dirname(__FILE__) . "/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetTripIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/GetGoogleResponseProcessor.php");
    require_once(dirname(__FILE__) . "/AddNoteProcessor.php");

    class ArchiveTripProcessor extends Processor {    
        public function process($input) {
            global $configuration, $databaseProvider;

            $tripRow = $databaseProvider
                ->statementBuilder("SELECT te.id, te.trip_id, te.start, te.end, ti.name FROM trip_event te INNER JOIN trip_identifier ti ON te.trip_id = ti.id WHERE ti.id = ?")
                ->withParameters($input["tripId"])
                ->getSingleRow();

            if ($tripRow == NULL) {
                throw new InvalidArgumentException("The trip " . $input["tripId"] . " could not be archived because it does not exist.");
            }
            
            $archivedTripIdentifier = (new GetTripIdentifierProcessor())
                ->process(array(
                    "name" => $tripRow["name"]));

            (new AddNoteProcessor())
                ->process(array(
                    "tripId" => $archivedTripIdentifier->getId(), 
                    "content" => date("j.n.Y", $tripRow["start"]) . " - " . date("j.n.Y", $tripRow["end"])));

            $placeRows = $databaseProvider
                ->statementBuilder("SELECT pe.id, pe.place_id, pe.start, pe.end, pi.timezone FROM place_event pe INNER JOIN place_identifier pi ON pe.place_id = pi.id WHERE pe.trip_id = ?")
                ->withParameters($input["tripId"])
                ->getResultSet();

            foreach ($placeRows as &$placeRow) {
                $timeOffset = $this->getTimezoneOffset($placeRow["start"], $configuration["homeLocation"]["timezone"], $placeRow["timezone"]);

                $databaseProvider
                    ->statementBuilder("INSERT INTO place_candidate_event (place_id, trip_id, start, end) VALUES (?, ?, ?, ?)")
                    ->withParameters($placeRow["place_id"], $archivedTripIdentifier->getId(), $placeRow["start"] - $timeOffset - $tripRow["start"], $placeRow["end"] - $timeOffset - $tripRow["start"])
                    ->execute();
                    
                $this->deleteEvent("places", $placeRow["id"]);
            }

            $this->deleteEvent("trips", $tripRow["id"]);

            $databaseProvider
                ->statementBuilder("UPDATE note SET trip_id = ? WHERE trip_id = ?")
                ->withParameters($archivedTripIdentifier->getId(), $input["tripId"])
                ->execute();
            
            return TRUE;
        }

        public function getRequiredArguments() {
            return array("tripId");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
        
        private function deleteEvent($calendar, $event) {            
            $eventId = explode("@", $event)[0];
            $calendarId = (new GetCalendarIdentifierProcessor())
                ->process(array(
                    "name" => $calendar));

            return (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "DELETE", 
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events/" . $eventId));
        }

        private function getTimezoneOffset($timestamp, $fromTimezone, $toTimezone) {
            $timezone = new DateTimeZone($fromTimezone);
            $dateTimeHome = new DateTime(date('m/d/Y H:i:s', $timestamp), new DateTimeZone($toTimezone));
            return $timezone->getOffset($dateTimeHome) - (new DateTimeZone($toTimezone))->getOffset($dateTimeHome);
        }
    }
?>