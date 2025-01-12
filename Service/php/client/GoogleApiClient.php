<?php
    class GoogleApiClient {
        public function createAlbum($albumName) : string {
            $payload = array(
                "album" => array(
                    "title" => $albumName));
            $apiResponse = $this->executeRequest("POST", "https://photoslibrary.googleapis.com/v1/albums", array(), $payload);

            if (!isset($apiResponse["id"])) {
                throw new RuntimeException("The album could not be created. " . $apiResponse["message"]);
            }

            return $apiResponse["id"];
        }

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

            $contentType = "multipart/related;boundary=" . $separator;
            $this->executeRequest("POST", "https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart", array(), $payload, $contentType);

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
             
            $this->executeRequest("POST", "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events", array(), $payload);
                    
            // TODO: Return whether the event was created.
            return TRUE;
        }

        public function deleteCalendarEvent($calendar, $eventId) : bool {
            $this->executeRequest("DELETE", "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events/" . str_replace("@google.com", "", $eventId));

            // TODO: Return whether the event was deleted.
            return TRUE;
        }

        public function updateCalendarEventSummary($calendar, $eventId, $name) : bool {
            $payload = array(
                "summary" => $name);
            $this->executeRequest("PATCH", "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events/" . str_replace("@google.com", "", $eventId), array(), $payload);

            // TODO: Return whether the event was updated.
            return TRUE;
        }

        public function updateCalendarEventLocation($calendar, $eventId, $location) : bool {
            $payload = array(
                "location" => $location);
            $this->executeRequest("PATCH", "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events/" . str_replace("@google.com", "", $eventId), array(), $payload);

            // TODO: Return whether the event was updated.
            return TRUE;
        }

        public function updateCalendarEventDates($calendar, $eventId, $start, $end) : bool {
            global $configuration;

            $payload = array(
                "start" => array(
                    "dateTime" => date(DATE_RFC3339, $start),
                    "timeZone" => $configuration["homeLocation"]["timezone"]),
                "end" => array(
                    "dateTime" => date(DATE_RFC3339, $end),
                    "timeZone" => $configuration["homeLocation"]["timezone"]));
            $this->executeRequest("PATCH", "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events/" . str_replace("@google.com", "", $eventId), array(), $payload);

            // TODO: Return whether the event was updated.
            return TRUE;
        }

        public function watchCalendar($calendar, $channelId, $url, $token = NULL) : bool {
            global $configuration;
                    
            $payload = array(
                "id" => $channelId,
                "type" => "web_hook",
                "address" => $url,
                "params" => array("ttl" => $configuration["googleCalendarApi"]["ttl"]));

            if ($token !== NULL) {
                $payload["token"] = $token;
            }

            $this->executeRequest("POST", "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events/watch", array(), $payload);
            
            // TODO: Return whether the calendar is watched now.
            return TRUE;
        }

        public function updateAlbumName($externalId, $name) : bool {
            $payload = array(
                "title" => $name);
            $this->executeRequest("PATCH", "https://photoslibrary.googleapis.com/v1/albums/" . $externalId . "?updateMask=title", array(), $payload);
                        
            // TODO: Return whether the album was updated.
            return TRUE;
        }

        public function updateAlbumMainPhoto($externalAlbumId, $externalPhotoId) : bool {
            $payload = array(
                "coverPhotoMediaItemId" => $externalPhotoId);
            $this->executeRequest("PATCH", "https://photoslibrary.googleapis.com/v1/albums/" . $externalAlbumId . "?updateMask=coverPhotoMediaItemId", array(), $payload);
                        
            // TODO: Return whether the album was updated.
            return TRUE;
        }

        public function getAlbums($pageToken = NULL) : array {
            $queryParameters = "?pageSize=50";

            if ($pageToken != NULL) {
                $queryParameters .= "&pageToken=" . $pageToken;
            }

            $apiResponse = $this->executeRequest("GET", "https://photoslibrary.googleapis.com/v1/albums" . $queryParameters);

            if (isset($apiResponse["error"])) {
                throw new RuntimeException($apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function getAlbum($externalAlbumId) : mixed {
            $apiResponse = $this->executeRequest("GET", "https://photoslibrary.googleapis.com/v1/albums/" . $externalAlbumId);

            if (isset($apiResponse["error"])) {
                throw new RuntimeException($apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function getMediaItem($externalPhotoId) : mixed {
            $apiResponse = $this->executeRequest("GET", "https://photoslibrary.googleapis.com/v1/mediaItems/" . $externalPhotoId);
                    
            if (isset($apiResponse["error"])) {
                throw new RuntimeException($apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function getMediaItems($externalAlbumId, $pageToken = NULL) : mixed {
            $payload = array(
                "albumId" => $externalAlbumId, 
                "pageSize" => 100);

            if ($pageToken != NULL) {
                $payload["pageToken"] = $pageToken;
            }

            return $this->executeRequest("POST", "https://photoslibrary.googleapis.com/v1/mediaItems:search", array(), $payload);
        }

        public function createPhotos($externalAlbumId, $newPhotos, $externalReplacedPhotoId = NULL) : array {
            $newMediaItems = array();

            foreach ($newPhotos as &$newPhoto) {
                $newMediaItems[] = array(
                    "description" => "",
                    "simpleMediaItem" => array(
                        "uploadToken" => $newPhoto["uploadToken"],
                        "fileName" => $newPhoto["fileName"]));
            }
            
            $payload = array(
                "albumId" => $externalAlbumId,
                "newMediaItems" => $newMediaItems);
                
            if ($externalReplacedPhotoId !== NULL) {
                $payload["albumPosition"] = array(
                    "position" => "AFTER_MEDIA_ITEM",
                    "relativeMediaItemId" => $externalReplacedPhotoId);
            }
            
            return $this->executeRequest("POST", "https://photoslibrary.googleapis.com/v1/mediaItems:batchCreate", array(), $payload)["newMediaItemResults"];
        }
        
        public function uploadPhoto($data) : string {
            $headers = array(
                "X-Goog-Upload-Content-Type" => "image/jpeg",
                "X-Goog-Upload-Protocol" => "raw");
            $uploadToken = $this->executeRequest("POST", "https://photoslibrary.googleapis.com/v1/uploads", $headers, base64_decode($data), "application/octet-stream");
                    
            if ($uploadToken === NULL) {
                throw new RuntimeException("The photo could not be uploaded.");
            }

            return $uploadToken;
        }

        private function executeRequest($method, $url, $headers = array(), $payload = NULL, $contentType = NULL) : mixed {
            global $httpClient;

            $convertedHeaders = array('Authorization: Bearer ' . $this->getGoogleApiAccessToken());
            if ($payload !== NULL) {
                if ($contentType !== NULL) {
                    $convertedHeaders[] = "Content-Type: " . $contentType;
                }
                else {
                    $convertedHeaders[] = "Content-Type: application/json";
                    $payload = json_encode($payload);
                }
            }

            foreach ($headers as $key => $value) {
                $convertedHeaders[] = $key . ": " . $value;
            }

            return $httpClient->executeRequest($method, $url, $convertedHeaders, $payload);
        }

        private function getGoogleApiAccessToken() : string {
            global $configuration, $httpClient;

            if (isset($_SESSION["googleApiAccessToken"]) 
                && isset($_SESSION["googleApiAccessTokenExpiration"]) 
                && $_SESSION["googleApiAccessTokenExpiration"] > time()) {
                return $_SESSION["googleApiAccessToken"];
            }
            
            $payload = array(
                "client_id" => $configuration["googleApiCredentials"]["clientId"],
                "client_secret" => $configuration["googleApiCredentials"]["clientSecret"],
                "redirect_uri" => BASE_URL,
                "refresh_token" => $configuration["googleApiCredentials"]["accessKey"],
                "grant_type" => "refresh_token",
                "access_type" => "offline");     

            $response = $httpClient->executeRequest("POST", "https://oauth2.googleapis.com/token", array("Content-Type: application/x-www-form-urlencoded"), http_build_query($payload));

            if (!isset($response["access_token"])) {
                throw new RuntimeException("The access token could not be obtained. Response: " . json_encode($response));
            }

            $_SESSION["googleApiAccessToken"] = $response["access_token"];
            $_SESSION["googleApiAccessTokenExpiration"] = time() + $response["expires_in"];

            return $_SESSION["googleApiAccessToken"];
        }

        private function getCalendarIdentifier($name) : string {            
            global $configuration;
    
            preg_match('/https:\/\/calendar\.google\.com\/calendar\/ical\/(.+@group\.calendar\.google\.com)\/.*/', rawurldecode($configuration["calendars"][$name]), $tokens);
            if (count($tokens) !== 2) {
                throw new InvalidArgumentException("The calendar " . $name . " does not exist.");
            }
            
            if ($tokens[1] === NULL) {
                throw new InvalidArgumentException("The calendar " . $name . " does not exist.");
            }

            return $tokens[1];
        }
    }
?>