<?php
    require_once(dirname(__FILE__) . "/../processor/GetCalendarIdentifierProcessor.php");
    require_once(dirname(__FILE__) . "/../processor/GetGoogleResponseProcessor.php");

    class GoogleApiClient {
        public function createFile($name, $folderId, $contentType, $content) : bool {
            $separator = "mpr_separator";

            $metadata = array("name" => $name);
            if ($folderId !== NULL) {
                $metadata["parents"] = array($folderId);
            }

            $payload = "--" . $separator . "\n"
                 . "Content-Type: application/json\n\n"
                 . json_encode($metadata) . "\n\n"
                 . "--" . $separator . "\n"
                 . "Content-Type: " . $contentType . "\n\n" 
                 . $content . "\n"
                 . "--" . $separator . "--";
    
            (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST", 
                    "url" => "https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart", 
                    "payload" => $payload, 
                    "contentType" => "multipart/related;boundary=" . $separator));

            // TODO: Return whether the file was created.
            return TRUE;
        }

        public function createCalendarEvent($calendar, $name, $address, $start, $end) : bool {    
            global $configuration;        
            
            $payload = array(
                "summary" => $name, 
                "start" => array(
                    "dateTime" => date(DATE_RFC3339, $start),
                    "timeZone" => $configuration["homeLocation"]["timezone"]), 
                "end" => array(
                    "dateTime" => date(DATE_RFC3339, $end),
                    "timeZone" => $configuration["homeLocation"]["timezone"]));

            if ($address !== NULL) {
                $payload["location"] = $address;
            }

            $calendarId = (new GetCalendarIdentifierProcessor())
                ->process(array(
                    "name" => $calendar));
                    
            (new GetGoogleResponseProcessor())
                ->process(array(
                    "method" => "POST",
                    "url" => "https://www.googleapis.com/calendar/v3/calendars/" . $calendarId . "/events", 
                    "payload" => json_encode($payload)));
                    
            // TODO: Return whether the event was created.
            return TRUE;
        }

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