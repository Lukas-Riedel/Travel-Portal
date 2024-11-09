<?php
    require_once(dirname(__FILE__) . "/../processor/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetGoogleResponseProcessor.php");

    class GoogleApiClient {
        public function deleteCalendarEvent($calendar, $eventId) : void {
            $calendarId = (new GetCalendarIdentifierProcessor())
                ->process(array(
                    "name" => $calendar));

            (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "DELETE", 
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events/" . explode("@", $eventId)[0]));
        }
    }
?>