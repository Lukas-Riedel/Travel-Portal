<?php
    require_once(dirname(__FILE__) . "/../processor/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetGoogleResponseProcessor.php");

    class GoogleApiClient {
        public function deleteCalendarEvent($calendar, $eventId) : bool {
            $calendarId = (new GetCalendarIdentifierProcessor())
                ->process(array(
                    "name" => $calendar));

            (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "DELETE", 
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events/" . explode("@", $eventId)[0]));

            // TODO: Return whether the event was deleted.
            return TRUE;
        }

        public function updateCalendarEventSummary($calendar, $eventId, $name) : bool {
            $calendarId = (new GetCalendarIdentifierProcessor())
                ->process(array(
                    "name" => $calendar));

            (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "PATCH", 
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events/" . str_replace("@google.com", "", $eventId),
                    "payload" => json_encode(array(
                        "summary" => $name))));

            // TODO: Return whether the event was updated.
            return TRUE;
        }

        public function updateAlbumName($externalId, $name) : bool {
            (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "PATCH", 
                    "url" => "https://photoslibrary.googleapis.com/v1/albums/" . $externalId . "?updateMask=title", 
                    "payload" => json_encode(array(
                        "title" => $name))));
                        
            // TODO: Return whether the album was updated.
            return TRUE;
        }
    }
?>