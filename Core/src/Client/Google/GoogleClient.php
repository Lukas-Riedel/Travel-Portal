<?php
    namespace Core\Client\Google;

    use Core\Client\Cache\CacheClient;
    use Core\Client\Calendar\Calendar;
    use Core\Client\Http\HttpClient;
    use Core\Common\CommonConstants;
    use Core\Service\Authentication\AuthenticationService;
    use Core\Service\Configuration\ConfigurationService;
    use Common\Client\Http\HttpMethod;
    use Monolog\Logger;

    // TODO: Switch to Google SDK.
    // TODO: Split to StorageClient, PhotoClient, GeocodingClient and CalendarClient, and make return values non-Google-specific.
    class GoogleClient {

        private const CREATE_ALBUM_URL = "https://photoslibrary.googleapis.com/v1/albums";
        private const GET_ALBUMS_URL = "https://photoslibrary.googleapis.com/v1/albums";
        private const UPDATE_ALBUM_URL_FORMAT = "https://photoslibrary.googleapis.com/v1/albums/%s?updateMask=%s";
        private const GET_ALBUM_URL_FORMAT = "https://photoslibrary.googleapis.com/v1/albums/%s";
        private const GET_MEDIA_ITEM_URL_FORMAT = "https://photoslibrary.googleapis.com/v1/mediaItems/%s";
        private const GET_MEDIA_ITEMS_URL = "https://photoslibrary.googleapis.com/v1/mediaItems:search";
        private const BATCH_CREATE_MEDIA_ITEMS_URL = "https://photoslibrary.googleapis.com/v1/mediaItems:batchCreate";
        private const UPLOAD_MEDIA_ITEM_URL = "https://photoslibrary.googleapis.com/v1/uploads";

        private const CREATE_FILE_URL = "https://www.googleapis.com/drive/v3/files";
        private const UPLOAD_FILE_URL_FORMAT = "https://www.googleapis.com/upload/drive/v3/files?uploadType=%s";
        private const GET_FILES_URL_FORMAT = "https://www.googleapis.com/drive/v3/files?pageSize=%d&q=%s";

        private const CREATE_CALENDAR_EVENT_URL_FORMAT = "https://www.googleapis.com/calendar/v3/calendars/%s/events";
        private const GET_CALENDAR_EVENTS_URL_FORMAT = "https://www.googleapis.com/calendar/v3/calendars/%s/events";
        private const ACCESS_CALENDAR_EVENT_URL_FORMAT = "https://www.googleapis.com/calendar/v3/calendars/%s/events/%s";
        private const WATCH_CALENDAR_EVENTS_URL_FORMAT = "https://www.googleapis.com/calendar/v3/calendars/%s/events/watch";
        
        // TODO: Do not hardcode the language here.
        private const GET_LOCATION_ENDPOINT_FORMAT = "https://maps.googleapis.com/maps/api/geocode/json?key=%s&language=cs&address=%s";
        private const GET_ADDRESS_ENDPOINT_FORMAT = "https://maps.googleapis.com/maps/api/geocode/json?key=%s&language=cs&latlng=%s,%s";
        private const GET_TIMEZONE_ENDPOINT_FORMAT = "https://maps.googleapis.com/maps/api/timezone/json?key=%s&location=%s,%s&timestamp=0";

        private const MULTIPART_SEPARATOR = "mpr_separator";
        private const EVENT_IDENTIFIER_SUFFIX = "@google.com";
        private const HEADER_FORMAT = "%s: %s";
        
        private const FOLDER_LOCK_FORMAT = "GoogleClient:Lock:Folder:%s";
        private const FOLDER_LOCK_TTL = 10;

        private readonly CacheClient $distributedCacheClient;
        private readonly HttpClient $httpClient;

        private readonly Logger $logger;

        private readonly string $googleMapsApiKey;

        private ?ConfigurationService $configurationService;
        private ?AuthenticationService $authenticationService;

        public function __construct(CacheClient $distributedCacheClient, HttpClient $httpClient, Logger $logger, string $googleMapsApiKey) {
            $this->distributedCacheClient = $distributedCacheClient;
            $this->httpClient = $httpClient;
            $this->logger = $logger;
            $this->googleMapsApiKey = $googleMapsApiKey;
            $this->configurationService = null;
            $this->authenticationService = null;
        }

        public function setConfigurationService(ConfigurationService $configurationService) : void {
            $this->configurationService = $configurationService;
        }

        public function setAuthenticationService(AuthenticationService $authenticationService) : void {
            $this->authenticationService = $authenticationService;
        }

        public function getLocation(string $address) : mixed {
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_LOCATION_ENDPOINT_FORMAT, $this->googleMapsApiKey, urlencode($address)));

            if ($apiResponse["status"] === "OK") {
                if (count($apiResponse["results"]) > 0) {
                    return $apiResponse["results"][0];
                }
            }

            return null;
        }

        public function getTimezone(float $latitude, float $longitude) : ?string {
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_TIMEZONE_ENDPOINT_FORMAT, $this->googleMapsApiKey, $latitude, $longitude));
            return array_key_exists("timeZoneId", $apiResponse) ? $apiResponse["timeZoneId"] : null;           
        }

        public function getAddress(float $latitude, float $longitude) : ?string {
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_ADDRESS_ENDPOINT_FORMAT, $this->googleMapsApiKey, $latitude, $longitude));

            if ($apiResponse["status"] === "OK") {
                if (count($apiResponse["results"]) > 0) {
                    if (isset($apiResponse["results"][0]["formatted_address"])) {
                        return $apiResponse["results"][0]["formatted_address"];
                    }
                }
            } 
            
            return null;
        }

        public function createFolder(string $name, ?string $folderId) : string {
            $payload = array(
                "name" => $name,
                "mimeType" => "application/vnd.google-apps.folder"
            );

            if ($folderId !== null) {
                $payload["parents"] = array($folderId);
            }

            $apiResponse = $this->executeRequest(HttpMethod::POST, self::CREATE_FILE_URL, array(), $payload);
            
            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The folder could not be created. Reason: " . $apiResponse["error"]["message"]);
            }

            return $apiResponse["id"];
        }

        public function createFile(string $name, ?string $folderId, string $contentType, string $content) : string {
            $metadata = array("name" => $name);

            if ($folderId !== null) {
                $metadata["parents"] = array($folderId);
            }

            $payload = "--" . self::MULTIPART_SEPARATOR . "\n"
                 . "Content-Type: application/json\n\n"
                 . json_encode($metadata) . "\n\n"
                 . "--" . self::MULTIPART_SEPARATOR . "\n"
                 . "Content-Type: " . $contentType . "\n\n" 
                 . $content . "\n"
                 . "--" . self::MULTIPART_SEPARATOR . "--";

            $apiResponse = $this->executeRequest(HttpMethod::POST, sprintf(self::UPLOAD_FILE_URL_FORMAT, "multipart"), array(),
                $payload, "multipart/related;boundary=" . self::MULTIPART_SEPARATOR);
            
            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The file could not be created. Reason: " . $apiResponse["error"]["message"]);
            }

            return $apiResponse["id"];
        }

        public function getOrCreateFolderId(string $name, ?string $folderId) : string {
            $folder = $this->getFolder($name, $folderId);
            if ($folder !== null) {
                return $folder;
            }

            $this->distributedCacheClient->lock(sprintf(self::FOLDER_LOCK_FORMAT, $name), self::FOLDER_LOCK_TTL, 
                function() use(&$name, &$folderId, &$folder) {
                    $folder = $this->getFolder($name, $folderId);
                    if ($folder === null) {
                        $folder = $this->createFolder($name, $folderId);
                    }
                });
            
            return $folder;
        }

        public function getFolder(string $name, ?string $folderId) : ?string {
            return $this->getFile($name, "application/vnd.google-apps.folder", $folderId);
        }

        public function getFile(string $name, string $mimeType, ?string $folderId) : ?string {
            $queryTokens = array("trashed = false");
            if ($name !== null) {
                $queryTokens[] = "name = '{$name}'";
            }
            if ($mimeType !== null) {
                $queryTokens[] = "mimeType = '{$mimeType}'";
            }
            if ($folderId !== null) {
                $queryTokens[] = "'{$folderId}' in parents";
            }

            $apiResponse = $this->executeRequest(HttpMethod::GET, sprintf(self::GET_FILES_URL_FORMAT, 2, rawurlencode(implode(" and ", $queryTokens))));

            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("Files could not be obtained. Reason: " . $apiResponse["error"]["message"]);
            }

            $files = $apiResponse["files"];
            if (count($files) > 1) {
                throw new \RuntimeException("Multiple files named '{$name}' were found.");
            }

            return count($files) === 1 ? $files[0]["id"] : null;
        }

        public function createCalendarEvent(Calendar $calendar, string $name, ?string $address, int $start, int $end,?string $startTimezone, ?string $endTimezone) : string {
            $payload = array(
                "summary" => $name, 
                "start" => array(
                    "dateTime" => date(DATE_RFC3339, $start),
                    "timeZone" => date_default_timezone_get()
                ), 
                "end" => array(
                    "dateTime" => date(DATE_RFC3339, $end),
                    "timeZone" => date_default_timezone_get()
                )
            );

            if ($startTimezone !== null) {
                $payload["start"]["timeZone"] = $startTimezone;
            }

            if ($endTimezone !== null) {
                $payload["end"]["timeZone"] = $endTimezone;
            }

            if ($address !== null) {
                $payload["location"] = $address;
            }
             
            $apiResponse = $this->executeRequest(HttpMethod::POST, sprintf(self::CREATE_CALENDAR_EVENT_URL_FORMAT, $this->getCalendarIdentifier($calendar)), array(), $payload);
            
            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The calendar event could not be created. Reason: " . $apiResponse["error"]["message"]);
            }

            return $apiResponse["id"];
        }

        public function deleteCalendarEvent(Calendar $calendar, string $eventId) : bool {
            $apiResponse = $this->executeRequest(HttpMethod::DELETE, sprintf(self::ACCESS_CALENDAR_EVENT_URL_FORMAT, $this->getCalendarIdentifier($calendar), $this->getEventIdentifier($eventId)));

            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The calendar event could not be deleted. Reason: " . $apiResponse["error"]["message"]);
            }

            return true;
        }

        public function updateCalendarEventName(Calendar $calendar, string $eventId, string $name) : bool {
            $payload = array("summary" => $name);

            $apiResponse = $this->executeRequest(HttpMethod::PATCH, sprintf(self::ACCESS_CALENDAR_EVENT_URL_FORMAT, $this->getCalendarIdentifier($calendar), $this->getEventIdentifier($eventId)), array(), $payload);

            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The calendar event name could not be updated. Reason: " . $apiResponse["error"]["message"]);
            }

            return true;
        }

        public function updateCalendarEventLocation(Calendar $calendar, string $eventId, string $location) : bool {
            $payload = array("location" => $location);

            $this->logger->info("Updating the '$calendar->value:$eventId' event location to '$location'...");

            $apiResponse = $this->executeRequest(HttpMethod::PATCH, sprintf(self::ACCESS_CALENDAR_EVENT_URL_FORMAT, $this->getCalendarIdentifier($calendar), $this->getEventIdentifier($eventId)), array(), $payload);

            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The calendar event location could not be updated. Reason: " . $apiResponse["error"]["message"]);
            }

            return true;
        }

        public function updateCalendarEventStartEnd(Calendar $calendar, string $eventId, ?int $start, ?int $end, ?string $startTimezone, ?string $endTimezone) : bool {
            $payload = $this->executeRequest(HttpMethod::GET, sprintf(self::ACCESS_CALENDAR_EVENT_URL_FORMAT, $this->getCalendarIdentifier($calendar), $this->getEventIdentifier($eventId)));

            if (!array_key_exists("start", $payload) || !array_key_exists("end", $payload)) {
                return false;
            }

            $original = $payload;
            
            $homeTimezone = new \DateTimeZone($this->configurationService->getConfigurationEntry("homeLocation")["timezone"]);

            if ($start !== null) {
                $startDt = (new \DateTimeImmutable())->setTimestamp($start)->setTimezone($homeTimezone);

                if (array_key_exists("dateTime", $payload["start"])) {
                    $payload["start"]["dateTime"] = $startDt->format(\DateTime::RFC3339);
                }
                else {
                    $payload["start"]["date"] = $startDt->format(CommonConstants::YMD_DATE_FORMAT);
                }
            }

            if ($startTimezone !== null && array_key_exists("dateTime", $payload["start"])) {
                $payload["start"]["timeZone"] = $startTimezone;
            }

            if ($end !== null) {
                $endDt = (new \DateTimeImmutable())->setTimestamp($end)->setTimezone($homeTimezone);

                if (array_key_exists("dateTime", $payload["end"])) {
                    $payload["end"]["dateTime"] = $endDt->format(\DateTime::RFC3339);
                }
                else {
                    $payload["end"]["date"] = $endDt->format(CommonConstants::YMD_DATE_FORMAT);
                }
            }

            if ($endTimezone !== null && array_key_exists("dateTime", $payload["end"])) {
                $payload["end"]["timeZone"] = $endTimezone;
            }

            $this->logger->info("Updating the '$calendar->value:$eventId' event dates from '" . json_encode($original["start"]) . "' - '" . json_encode($original["end"]) . "' to '" . json_encode($payload["start"]) . "' - '" . json_encode($payload["end"]) . "'...");

            $apiResponse = $this->executeRequest(HttpMethod::PUT, sprintf(self::ACCESS_CALENDAR_EVENT_URL_FORMAT, $this->getCalendarIdentifier($calendar), $this->getEventIdentifier($eventId)), array(), $payload);

            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The calendar event dates could not be updated. Reason: " . $apiResponse["error"]["message"]);
            }

            return true;
        }

        public function updateCalendarEventAllDayDates(Calendar $calendar, string $eventId, ?int $start, ?int $end) : bool {
            $payload = $this->executeRequest(HttpMethod::GET, sprintf(self::ACCESS_CALENDAR_EVENT_URL_FORMAT, $this->getCalendarIdentifier($calendar), $this->getEventIdentifier($eventId)));

            if (!array_key_exists("start", $payload) || !array_key_exists("end", $payload)) {
                return false;
            }
            
            $original = $payload;

            if ($start !== null) {
                $payload["start"] = array("date" => date(CommonConstants::YMD_DATE_FORMAT, $start));             
            }

            if ($end !== null) {
                $payload["end"] = array("date" => date(CommonConstants::YMD_DATE_FORMAT, $end));
            }
            
            $this->logger->info("Updating the '$calendar->value:$eventId' event dates from ''" . json_encode($original["start"]) . "' - '" . json_encode($original["end"]) . "' to '" . json_encode($payload["start"]) . "' - '" . json_encode($payload["end"]) . "'...");

            $apiResponse = $this->executeRequest(HttpMethod::PUT, sprintf(self::ACCESS_CALENDAR_EVENT_URL_FORMAT, $this->getCalendarIdentifier($calendar), $this->getEventIdentifier($eventId)), array(), $payload);

            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The calendar event dates could not be updated. Reason: " . $apiResponse["error"]["message"]);
            }

            return true;
        }

        public function getCalendarEvents(Calendar $calendar, ?string $pageToken = null) : array {
            $queryParameters = "?maxResults=2500";

            if ($pageToken !== null) {
                $queryParameters .= "&pageToken=" . $pageToken;
            }

            $apiResponse = $this->executeRequest(HttpMethod::GET, sprintf(self::GET_CALENDAR_EVENTS_URL_FORMAT . $queryParameters, $this->getCalendarIdentifier($calendar)));

            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("Calendar events could not be obtained. Reason: " . $apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function watchCalendar(Calendar $calendar, string $channelId, string $url, int $ttl, ?string $token = null) : bool {                    
            $payload = array(
                "id" => $channelId,
                "type" => "web_hook",
                "address" => $url,
                "params" => array("ttl" => $ttl)
            );

            if ($token !== null) {
                $payload["token"] = $token;
            }

            $apiResponse = $this->executeRequest(HttpMethod::POST, sprintf(self::WATCH_CALENDAR_EVENTS_URL_FORMAT, $this->getCalendarIdentifier($calendar)), array(), $payload);
            
            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The calendar could not be watched. Reason: " . $apiResponse["error"]["message"]);
            }

            return true;
        }
    
        public function createAlbum(string $name) : string {
            $payload = array(
                "album" => array("title" => $name)
            );

            $apiResponse = $this->executeRequest(HttpMethod::POST, self::CREATE_ALBUM_URL, array(), $payload);

            if (!isset($apiResponse["id"])) {
                throw new \RuntimeException("The album could not be created. Reason: " . $apiResponse["message"]);
            }

            return $apiResponse["id"];
        }

        public function updateAlbumName(string $externalAlbumId, string $name) : bool {
            $payload = array("title" => $name);

            $apiResponse = $this->executeRequest(HttpMethod::PATCH, sprintf(self::UPDATE_ALBUM_URL_FORMAT, $externalAlbumId, "title"), array(), $payload);
            
            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The album name could not be updated. Reason: " . $apiResponse["error"]["message"]);
            }

            return true;
        }

        public function updateAlbumMainPhoto(string $externalAlbumId, string $externalPhotoId) : bool {
            $payload = array("coverPhotoMediaItemId" => $externalPhotoId);

            $apiResponse = $this->executeRequest(HttpMethod::PATCH, sprintf(self::UPDATE_ALBUM_URL_FORMAT, $externalAlbumId, "coverPhotoMediaItemId"), array(), $payload);
            
            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The album main photo could not be updated. Reason: " . $apiResponse["error"]["message"]);
            }

            return true;
        }

        public function getAlbums(?string $pageToken = null) : array {
            $queryParameters = "?pageSize=50";

            if ($pageToken !== null) {
                $queryParameters .= "&pageToken=" . $pageToken;
            }

            $apiResponse = $this->executeRequest(HttpMethod::GET, self::GET_ALBUMS_URL . $queryParameters);

            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("Albums could not be obtained. Reason: " . $apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function getAlbum(string $externalAlbumId) : mixed {
            $apiResponse = $this->executeRequest(HttpMethod::GET, sprintf(self::GET_ALBUM_URL_FORMAT, $externalAlbumId));

            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The album could not be obtained. Reason: " . $apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function getPhoto(string $externalPhotoId) : mixed {
            $apiResponse = $this->executeRequest(HttpMethod::GET, sprintf(self::GET_MEDIA_ITEM_URL_FORMAT, $externalPhotoId));
                    
            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("The photo could not be obtained. Reason: " . $apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function getPhotos(string $externalAlbumId, ?string $pageToken = null) : mixed {
            $payload = array(
                "albumId" => $externalAlbumId, 
                "pageSize" => 100
            );

            if ($pageToken != null) {
                $payload["pageToken"] = $pageToken;
            }

            $apiResponse = $this->executeRequest(HttpMethod::POST, self::GET_MEDIA_ITEMS_URL, array(), $payload);
            
            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("Photos could not be obtained. Reason: " . $apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }

        public function createPhotos(string $externalAlbumId, array $newPhotos, ?string $externalReplacedPhotoId = null) : array {
            $newMediaItems = array();
            foreach ($newPhotos as &$newPhoto) {
                $newMediaItems[] = array(
                    "description" => "",
                    "simpleMediaItem" => array(
                        "uploadToken" => $newPhoto["uploadToken"],
                        "fileName" => $newPhoto["fileName"]
                    )
                );
            }
            
            $payload = array(
                "albumId" => $externalAlbumId,
                "newMediaItems" => $newMediaItems
            );
                
            if ($externalReplacedPhotoId !== null) {
                $payload["albumPosition"] = array(
                    "position" => "AFTER_MEDIA_ITEM",
                    "relativeMediaItemId" => $externalReplacedPhotoId
                );
            }
            
            $apiResponse = $this->executeRequest(HttpMethod::POST, self::BATCH_CREATE_MEDIA_ITEMS_URL, array(), $payload);

            if (isset($apiResponse["error"])) {
                throw new \RuntimeException("Photos could not be created. Reason: " . $apiResponse["error"]["message"]);
            }

            return $apiResponse;
        }
        
        public function uploadPhoto(string $data) : string {
            $headers = array(
                "X-Goog-Upload-Content-Type" => "image/jpeg",
                "X-Goog-Upload-Protocol" => "raw"
            );

            $uploadToken = $this->executeRequest(HttpMethod::POST, self::UPLOAD_MEDIA_ITEM_URL, $headers, base64_decode($data), "application/octet-stream");
                    
            if ($uploadToken === null) {
                throw new \RuntimeException("The photo could not be uploaded.");
            }

            return $uploadToken;
        }

        private function executeRequest(HttpMethod $method, string $url, array $headers = array(), mixed $payload = null, ?string $contentType = null) : mixed {
            $convertedHeaders = array(sprintf(self::HEADER_FORMAT, "Authorization", "Bearer " . $this->authenticationService->getGoogleApiAccessToken()));
            if ($payload !== null) {
                if ($contentType !== null) {
                    $convertedHeaders[] = sprintf(self::HEADER_FORMAT, "Content-Type", $contentType);
                }
                else {
                    $convertedHeaders[] = sprintf(self::HEADER_FORMAT, "Content-Type", "application/json");
                    $payload = json_encode($payload);
                }
            }

            foreach ($headers as $key => $value) {
                $convertedHeaders[] = sprintf(self::HEADER_FORMAT, $key, $value);
            }

            return $this->httpClient->executeRequest($method, $url, $convertedHeaders, $payload);
        }

        private function getCalendarIdentifier(Calendar $calendar) : string {    
            preg_match('/https:\/\/calendar\.google\.com\/calendar\/ical\/(.+@group\.calendar\.google\.com)\/.*/',
                rawurldecode($this->configurationService->getConfigurationEntry("calendars")[$calendar->value]), $tokens);
            if (count($tokens) !== 2 || $tokens[1] === null) {
                throw new \InvalidArgumentException("The calendar '{$calendar->value}' does not exist or has invalid format.");
            }

            return $tokens[1];
        }

        // This may not be needed anymore after the switch to Google IDs from iCal IDs, but it shouldn't really bother anyone.
        private function getEventIdentifier(string $eventId) : string {
            return str_replace(self::EVENT_IDENTIFIER_SUFFIX, "", $eventId);
        }
    }
?>