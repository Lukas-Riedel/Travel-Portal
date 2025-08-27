<?php
    // TODO: Across this class return what API returns - e.g., for createFile, return the File object, or null if any error occurred.
    class GoogleApiClient {
        private const GOOGLE_API_ACCESS_TOKEN_CACHE_KEY = "GoogleApiClient:GoogleApiAccessToken";

        public function createAlbum($albumName) : string {
            $payload = array(
                "album" => array(
                    "title" => $albumName));
            $apiResponse = $this->executeRequest(HttpMethod::POST, "https://photoslibrary.googleapis.com/v1/albums", array(), $payload);

            if (!isset($apiResponse["id"])) {
                throw new RuntimeException("The album could not be created. " . $apiResponse["message"]);
            }

            return $apiResponse["id"];
        }

        public function getFolder($name, $folderId) : mixed {
            return $this->getFile($name, "application/vnd.google-apps.folder", $folderId);
        }

        public function getFile($name, $mimeType, $folderId) : mixed {
            $queryTokens = array();
            if ($name !== null) {
                $queryTokens[] = "name = '{$name}'";
            }
            if ($mimeType !== null) {
                $queryTokens[] = "mimeType = '{$mimeType}'";
            }
            if ($folderId !== null) {
                $queryTokens[] = "'{$folderId}' in parents";
            }

            $apiResponse = $this->executeRequest(HttpMethod::GET, "https://www.googleapis.com/drive/v3/files?pageSize=2&q=" . rawurlencode(implode(" and ", $queryTokens)));

            if (isset($apiResponse["error"])) {
                throw new RuntimeException($apiResponse["error"]["message"]);
            }

            $files = $apiResponse["files"];

            return count($files) === 1 ? $files[0] : null;
        }

        public function createFolder($name, $folderId) : ?string {
            $payload = array(
                "name" => $name,
                "mimeType" => "application/vnd.google-apps.folder"
            );

            if ($folderId !== null) {
                $payload["parents"] = array($folderId);
            }

            $file = $this->executeRequest(HttpMethod::POST, "https://www.googleapis.com/drive/v3/files", array(), $payload);

            return isset($file["id"]) ? $file["id"] : null;
        }

        public function createFile($name, $folderId, $contentType, $content) : bool {
            $separator = "mpr_separator";

            $metadata = array("name" => $name);
            if ($folderId !== null) {
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
            $this->executeRequest(HttpMethod::POST, "https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart", array(), $payload, $contentType);

            // TODO: Return whether the file was created.
            return true;
        }

        // TODO: Change string $calendar to Calendar $calendar and update usages.
        public function createCalendarEvent($calendar, $name, $address, $start, $end) : bool {    
            global $configurationService;        
            
            $homeTimezone = $configurationService->getConfigurationEntry("homeLocation")["timezone"];
            $payload = array(
                "summary" => $name, 
                "start" => array(
                    "dateTime" => date(DATE_RFC3339, $start),
                    "timeZone" => $homeTimezone), 
                "end" => array(
                    "dateTime" => date(DATE_RFC3339, $end),
                    "timeZone" => $homeTimezone));

            if ($address !== null) {
                $payload["location"] = $address;
            }
             
            $this->executeRequest(HttpMethod::POST, "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events", array(), $payload);
                    
            // TODO: Return whether the event was created.
            return true;
        }

        // TODO: Change string $calendar to Calendar $calendar and update usages.
        public function deleteCalendarEvent($calendar, $eventId) : bool {
            $this->executeRequest(HttpMethod::DELETE, "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events/" . str_replace("@google.com", "", $eventId));

            // TODO: Return whether the event was deleted.
            return true;
        }

        // TODO: Change string $calendar to Calendar $calendar and update usages.
        public function updateCalendarEventSummary($calendar, $eventId, $name) : bool {
            $payload = array(
                "summary" => $name);
            $this->executeRequest(HttpMethod::PATCH, "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events/" . str_replace("@google.com", "", $eventId), array(), $payload);

            // TODO: Return whether the event was updated.
            return true;
        }

        // TODO: Change string $calendar to Calendar $calendar and update usages.
        public function updateCalendarEventLocation($calendar, $eventId, $location) : bool {
            $payload = array(
                "location" => $location);
            $this->executeRequest(HttpMethod::PATCH, "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events/" . str_replace("@google.com", "", $eventId), array(), $payload);

            // TODO: Return whether the event was updated.
            return true;
        }

        // TODO: Change string $calendar to Calendar $calendar and update usages.
        public function updateCalendarEventDates($calendar, $eventId, $start, $end) : bool {
            $requestUrl = "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events/" . str_replace("@google.com", "", $eventId);
            $event = $this->executeRequest(HttpMethod::GET, $requestUrl);

            if (!array_key_exists("start", $event) || !array_key_exists("end", $event)) {
                return false;
            }

            if (array_key_exists("dateTime", $event["start"])) {
                $event["start"]["dateTime"] = date(DATE_RFC3339, $start);
            }
            else {
                $event["start"]["date"] = date("Y-m-d", $start);
            }

            if (array_key_exists("dateTime", $event["end"])) {
                $event["end"]["dateTime"] = date(DATE_RFC3339, $end);
            }
            else {
                $event["end"]["date"] = date("Y-m-d", $end);
            }

            $this->executeRequest(HttpMethod::PUT, $requestUrl, array(), $event);

            // TODO: Return whether the event was updated.
            return true;
        }

        // TODO: Change string $calendar to Calendar $calendar and update usages.
        public function watchCalendar($calendar, $channelId, $url, $ttl, $token = null) : bool {                    
            $payload = array(
                "id" => $channelId,
                "type" => "web_hook",
                "address" => $url,
                "params" => array("ttl" => $ttl));

            if ($token !== null) {
                $payload["token"] = $token;
            }

            $this->executeRequest(HttpMethod::POST, "https://www.googleapis.com/calendar/v3/calendars/" . $this->getCalendarIdentifier($calendar) . "/events/watch", array(), $payload);
            
            // TODO: Return whether the calendar is watched now.
            return true;
        }

        public function updateAlbumName($externalId, $name) : bool {
            $payload = array(
                "title" => $name);
            $this->executeRequest(HttpMethod::PATCH, "https://photoslibrary.googleapis.com/v1/albums/" . $externalId . "?updateMask=title", array(), $payload);
                        
            // TODO: Return whether the album was updated.
            return true;
        }

        public function updateAlbumMainPhoto($externalAlbumId, $externalPhotoId) : bool {
            $payload = array(
                "coverPhotoMediaItemId" => $externalPhotoId);
            $this->executeRequest(HttpMethod::PATCH, "https://photoslibrary.googleapis.com/v1/albums/" . $externalAlbumId . "?updateMask=coverPhotoMediaItemId", array(), $payload);
                        
            // TODO: Return whether the album was updated.
            return true;
        }

        public function getAlbums($pageToken = null) : array {
            $queryParameters = "?pageSize=50";

            if ($pageToken != null) {
                $queryParameters .= "&pageToken=" . $pageToken;
            }

            $apiResponse = $this->executeRequest(HttpMethod::GET, "https://photoslibrary.googleapis.com/v1/albums" . $queryParameters);

            if (isset($apiResponse["error"])) {
                throw new RuntimeException($apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function getAlbum($externalAlbumId) : mixed {
            $apiResponse = $this->executeRequest(HttpMethod::GET, "https://photoslibrary.googleapis.com/v1/albums/" . $externalAlbumId);

            if (isset($apiResponse["error"])) {
                throw new RuntimeException($apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function getMediaItem($externalPhotoId) : mixed {
            $apiResponse = $this->executeRequest(HttpMethod::GET, "https://photoslibrary.googleapis.com/v1/mediaItems/" . $externalPhotoId);
                    
            if (isset($apiResponse["error"])) {
                throw new RuntimeException($apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function getMediaItems($externalAlbumId, $pageToken = null) : mixed {
            $payload = array(
                "albumId" => $externalAlbumId, 
                "pageSize" => 100);

            if ($pageToken != null) {
                $payload["pageToken"] = $pageToken;
            }

            return $this->executeRequest(HttpMethod::POST, "https://photoslibrary.googleapis.com/v1/mediaItems:search", array(), $payload);
        }

        public function createPhotos($externalAlbumId, $newPhotos, $externalReplacedPhotoId = null) : array {
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
                
            if ($externalReplacedPhotoId !== null) {
                $payload["albumPosition"] = array(
                    "position" => "AFTER_MEDIA_ITEM",
                    "relativeMediaItemId" => $externalReplacedPhotoId);
            }
            
            $response = $this->executeRequest(HttpMethod::POST, "https://photoslibrary.googleapis.com/v1/mediaItems:batchCreate", array(), $payload);
            return isset($response["newMediaItemResults"]) ? $response["newMediaItemResults"] : array();
        }
        
        public function uploadPhoto($data) : string {
            $headers = array(
                "X-Goog-Upload-Content-Type" => "image/jpeg",
                "X-Goog-Upload-Protocol" => "raw");
            $uploadToken = $this->executeRequest(HttpMethod::POST, "https://photoslibrary.googleapis.com/v1/uploads", $headers, base64_decode($data), "application/octet-stream");
                    
            if ($uploadToken === null) {
                throw new RuntimeException("The photo could not be uploaded.");
            }

            return $uploadToken;
        }

        private function executeRequest($method, $url, $headers = array(), $payload = null, $contentType = null) : mixed {
            global $httpClient;

            $convertedHeaders = array('Authorization: Bearer ' . $this->getGoogleApiAccessToken());
            if ($payload !== null) {
                if ($contentType !== null) {
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
            global $authenticationService, $cacheClient;

            $cachedGoogleApiAccessToken = $cacheClient->get(self::GOOGLE_API_ACCESS_TOKEN_CACHE_KEY);
            if ($cachedGoogleApiAccessToken !== null) {
                return $cachedGoogleApiAccessToken;
            }

            $googleAuthenticationResult = $authenticationService->getGoogleApiAccessToken();
            $cacheClient->set(self::GOOGLE_API_ACCESS_TOKEN_CACHE_KEY, $googleAuthenticationResult->getAccessToken(),
                $googleAuthenticationResult->getExpiresIn());

            return $googleAuthenticationResult->getAccessToken();
        }

        // TODO: Move to Calendar enum.
        private function getCalendarIdentifier($name) : string {            
            global $configurationService;
    
            preg_match('/https:\/\/calendar\.google\.com\/calendar\/ical\/(.+@group\.calendar\.google\.com)\/.*/', rawurldecode($configurationService->getConfigurationEntry("calendars")[$name]), $tokens);
            if (count($tokens) !== 2) {
                throw new InvalidArgumentException("The calendar " . $name . " does not exist.");
            }
            
            if ($tokens[1] === null) {
                throw new InvalidArgumentException("The calendar " . $name . " does not exist.");
            }

            return $tokens[1];
        }
    }
?>