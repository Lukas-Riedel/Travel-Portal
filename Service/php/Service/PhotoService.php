<?php
    require_once(dirname(__FILE__) . "/PhotoMapper.php");
    require_once(dirname(__FILE__) . "/../Model/Album.php");
    require_once(dirname(__FILE__) . "/../Model/Photo.php");
    require_once(dirname(__FILE__) . "/../Model/PendingPhoto.php");

    class PhotoService {

        private const FETCH_ALBUMS_ACTION_NAME = "FETCH_ALBUMS";
        private const FETCH_ALBUMS_ACTION_INTERVAL = 21600;

        private const PENDING_PHOTOS_EXPIRATION_INTERVAL = 86400;

        private const JPG_FILE_EXTENSION = ".jpg";

        private const ALBUM_THUMBNAIL_WIDTH = 350;
        private const ALBUM_THUMBNAIL_HEIGHT = 233;
        private const ALBUM_THUMBNAIL_CACHE_PATH = "cache/album";

        private readonly PhotoMapper $photoMapper;

        private readonly GoogleApiClient $googleApiClient;

        private readonly ConfigurationService $configurationService;
        
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(DatabaseProvider $databaseProvider, GoogleApiClient $googleApiClient, 
            ConfigurationService $configurationService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->photoMapper = new PhotoMapper($databaseProvider, $googleApiClient);
            $this->googleApiClient = $googleApiClient;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }
        
        public function getAlbum(string $albumId) : ?Album {
            return $this->photoMapper->selectAlbum($albumId);
        }

        public function createAlbum(PlaceIdentifier $placeIdentifier, int $timestamp) : Album {
            $albumName = $this->getAlbumName($placeIdentifier->getName(), $timestamp);
            $createdAlbumExternalId = $this->googleApiClient->createAlbum($albumName);
            $albumId = $this->getOrCreateAlbumId($createdAlbumExternalId);
            $this->updateAlbum($albumId);
            return $this->getAlbum($albumId);
        }

        public function updateAllAlbums() : void {            
            $filePaths = $this->doUpdateAlbums(NULL, FALSE);
            $this->prunePhysicalCache($filePaths);
        }

        public function updateAlbum(string $albumId, ?int $mainPhotoPosition = NULL) : void {            
            $this->createPendingPhotos($albumId);

            if ($mainPhotoPosition !== NULL) {
                $photos = $this->getPhotos($albumId);

                $mainPhotoPosition = $mainPhotoPosition - 1;
                if ($mainPhotoPosition < 0 || $mainPhotoPosition >= count($photos)) {
                    throw new RuntimeException("Cannot set main photo because there are only " . count($photos) . " photos in the album.");
                }

                $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);       
                if ($externalAlbumId === NULL) {
                    throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
                }
    
                $externalPhotoId = $this->photoMapper->selectPhotoExternalId($photos[$mainPhotoPosition]->getId());
                if ($externalPhotoId === NULL) {
                    throw new InvalidArgumentException("A photo with the identifier " . $photos[$mainPhotoPosition]->getId() . " does not exist.");
                }
    
                $this->googleApiClient->updateAlbumMainPhoto($externalAlbumId, $externalPhotoId);
            }

            $this->doUpdateAlbums($albumId, TRUE);
        }

        public function getPhoto(string $photoId) : ?Photo {
            return $this->photoMapper->selectPhoto($photoId);
        }

        public function getPhotos(string $albumId) : array {
            $photos = array();

            // Fetch photos.
            $response = $this->getPhotosResponse($albumId);
            while (isset($response["mediaItems"] )) {
                foreach ($response["mediaItems"] as &$mediaItem) {
                    $photos[] = new Photo(
                        $this->getOrCreatePhotoId($mediaItem["id"]), 
                        function() use(&$mediaItem) { return $mediaItem["baseUrl"]; },
                        $mediaItem["productUrl"],
                        isset($mediaItem["mediaMetadata"]["photo"]["focalLength"]) ? $mediaItem["mediaMetadata"]["photo"]["focalLength"] : NULL,
                        isset($mediaItem["mediaMetadata"]["photo"]["apertureFNumber"]) ? $mediaItem["mediaMetadata"]["photo"]["apertureFNumber"] : NULL,
                        isset($mediaItem["mediaMetadata"]["photo"]["exposureTime"]) ? doubleval(rtrim($mediaItem["mediaMetadata"]["photo"]["exposureTime"], "s")) : NULL,
                        isset($mediaItem["mediaMetadata"]["photo"]["isoEquivalent"]) ? $mediaItem["mediaMetadata"]["photo"]["isoEquivalent"] : NULL,
                        strtotime($mediaItem["mediaMetadata"]["creationTime"])
                    );
                }
                
                if (isset($response["nextPageToken"])) {
                    $response = $this->getPhotosResponse($albumId, $response["nextPageToken"]);
                }
                else {
                    $response = array();
                }
            }
        
            // Persist photos.
            $deletedPhotosCount = $this->photoMapper->deletePhotos($albumId);        
            foreach ($photos as &$photo) {
                $this->photoMapper->insertPhoto($photo, $albumId);
            }

            // The count of photos is different from what was previously stored in the database. Invalidate the album.
            if (count($photos) !== $deletedPhotosCount) {
                $this->eventPublisher->publishAlbumInvalidatedEvent($albumId);
            }

            return $photos;
        }

        public function updateAlbumName(string $albumId, string $oldPlaceName, string $newPlaceName) : bool {
            $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);
            $wasUpdated = $this->googleApiClient->updateAlbumName($externalAlbumId, str_replace($oldPlaceName, $newPlaceName, $this->getAlbum($albumId)->getName()));
            $this->updateAlbum($albumId);
            return $wasUpdated;
        }

        public function uploadPhoto(string $fileName, string $albumId, ?int $position, ?string $replacedPhotoId, string $data) : PendingPhoto {
            if ($position === NULL && $replacedPhotoId === NULL) {
                throw new InvalidArgumentException("Either the photo position or the identifier of the photo being replaced must be specified.");
            }
        
            $uploadToken = $this->googleApiClient->uploadPhoto($data);

            $pendingPhoto = new PendingPhoto(NULL, $albumId, $fileName, $position, $replacedPhotoId, $uploadToken);
            $this->photoMapper->insertPendingPhoto($pendingPhoto, self::PENDING_PHOTOS_EXPIRATION_INTERVAL);
            return $pendingPhoto;
        }
        
        private function doUpdateAlbums(?string $albumId, bool $forceOverwrite) : array {
            global $highlightService, $databaseProvider, $categoryService;
        
            $filePaths = array();
            $albums = array();
        
            // Fetch albums.
            $response = $this->getAlbumsResponse($albumId);
            while (isset($response["albums"])) {
                foreach ($response["albums"] as &$album) {
                    $mainPhotoId = NULL;
                    $mainImageUrl = NULL;

                    if (isset($album["coverPhotoMediaItemId"])) {
                        $fileName = $album["coverPhotoMediaItemId"] . self::JPG_FILE_EXTENSION;
                        $filePath = $this->getPhysicalCachePath() . "/" . $fileName;
            
                        if ($forceOverwrite || !file_exists($filePath)) {
                            file_put_contents($filePath, file_get_contents($album["coverPhotoBaseUrl"] 
                                . "=w" . self::ALBUM_THUMBNAIL_WIDTH
                                . "-h" . self::ALBUM_THUMBNAIL_HEIGHT));
                        }
            
                        $filePaths[] = $filePath;
                        $mainImageUrl = $this->configurationService->getBaseUrl() 
                            . "/" . self::ALBUM_THUMBNAIL_CACHE_PATH
                            . "/" . $fileName;
                        
                        $mainPhotoId = $this->getOrCreatePhotoId($album["coverPhotoMediaItemId"]);
                    }
        
                    $imagesCount = 0;
                    if (isset($album["mediaItemsCount"])) {
                        $imagesCount = intval($album["mediaItemsCount"]);
                    }

                    $currentAlbumId = $this->getOrCreateAlbumId($album["id"]);        
                    $albums[] = new Album($currentAlbumId, $album["title"], $mainPhotoId, $mainImageUrl, $album["productUrl"], $imagesCount, 0);

                    // TODO: This is temporary until there is proper support for highlights (Q2/2025).
                    // Remove global variables when removing this code.
                    if ($albumId !== NULL && isset($album["coverPhotoMediaItemId"])) {
                        $placeRow = $databaseProvider
                            ->statementBuilder("SELECT *, YEAR(FROM_UNIXTIME(start)) AS year FROM place_summary WHERE album_id = ?")
                            ->withParameters($currentAlbumId)
                            ->getFirstRow();
    
                        if ($placeRow !== NULL) {
                            $highlightService->createPlaceHighlight($placeRow["place_id"], $mainPhotoId);
    
                            if ($placeRow["trip_id"] !== NULL) {
                                $highlightService->createTripHighlight($placeRow["trip_id"], $mainPhotoId);
                                $highlightService->createYearHighlight($placeRow["year"], $mainPhotoId);
                            }    
    
                            foreach (explode(",", $placeRow["category_ids"]) as &$categoryId) {
                                $categoryIdentifier = $categoryService->getCategoryIdentifierById($categoryId);
                                if ($categoryIdentifier !== NULL && $categoryIdentifier->getCategory() !== CategoryCategory::Variable->value) {
                                    $highlightService->createCategoryHighlight($categoryId, $mainPhotoId);
                                }
                            }
                        }
                    }  
                    // End of temporary code.                          
                }
        
                if (isset($response["nextPageToken"])) {
                    $response = $this->getAlbumsResponse($albumId, $response["nextPageToken"]);
                }
                else {
                    $response = array();
                }
            }
        
            // Persist albums.
            $this->photoMapper->deleteAlbums($albumId);        
            foreach ($albums as &$album) {
                $this->photoMapper->insertAlbum($album);
            }

            // Trigger an event for updated albums.
            // As of now, the event is only triggered if the count of photos in the album has changed.
            if ($albumId === NULL) {
                $changedAlbumIds = $this->photoMapper->selectAlbumIdsWithOutdatedPhotos();
                foreach ($changedAlbumIds as &$changedAlbumId) {
                    $this->eventPublisher->publishAlbumUpdatedEvent($changedAlbumId);
                }
            }
            else {
                $this->eventPublisher->publishAlbumUpdatedEvent($albumId);
            }

            return $filePaths;
        }
        
        private function createPendingPhotos(string $albumId) : void {            
            // Process pending photos with fixed position.
            $pendingPhotos = $this->photoMapper->selectPendingPhotosWithFixedPosition($albumId);        
            while (count($pendingPhotos) > 0) {
                $newPhotos = array();
                foreach ($pendingPhotos as &$pendingPhoto) {
                    $newPhotos[] = array(
                        "uploadToken" => $pendingPhoto->getUploadToken(),
                        "fileName" => $pendingPhoto->getFileName()
                    );

                    $this->photoMapper->deletePendingPhoto($pendingPhoto->getId());
                }

                $this->createGooglePhotos($albumId, $newPhotos, NULL);                
                $pendingPhotos = $this->photoMapper->selectPendingPhotosWithFixedPosition($albumId);
            }
                   
            // Process pending photos with relative position.
            $pendingPhotos = $this->photoMapper->selectPendingPhotosWithRelativePosition($albumId);            
            foreach ($pendingPhotos as &$pendingPhoto) {
                $newPhoto = array(
                    "uploadToken" => $pendingPhoto->getUploadToken(),
                    "fileName" => $pendingPhoto->getFileName()
                );

                $this->photoMapper->deletePendingPhoto($pendingPhoto->getId());
                $createdMediaItemId = $this->createGooglePhotos($albumId, array($newPhoto), $pendingPhoto->getReplacedPhotoId())[0]["mediaItem"]["id"];
                $this->photoMapper->updatePhotoExternalId($pendingPhoto->getReplacedPhotoId(), $createdMediaItemId);
                $this->eventPublisher->publishPhotoInvalidatedEvent($pendingPhoto->getReplacedPhotoId());
            }
        }

        private function getPhysicalCachePath() : string {
            return dirname(__FILE__) . "/../../" . self::ALBUM_THUMBNAIL_CACHE_PATH;
        }

        private function prunePhysicalCache(array $usedFilePaths) : void {
            $existingFilePaths = array_filter((array) glob($this->getPhysicalCachePath() . "/*"));
            $unusedFilePaths = array_diff($existingFilePaths, $usedFilePaths);    
            array_map("unlink", $unusedFilePaths);
        }

        private function getAlbumName(string $placeName, int $timestamp) : string {
            return $placeName . " " . date("j.n.Y", $timestamp);
        }
        
        private function getOrCreateAlbumId(string $externalId) : string {
            $albumId = $this->photoMapper->selectAlbumId($externalId);
            if ($albumId !== NULL) {
                return $albumId;
            }

            $this->photoMapper->insertAlbumId($externalId);

            return $this->photoMapper->selectAlbumId($externalId);
        }
    
        private function getOrCreatePhotoId(string $externalId) : string {
            $photoId = $this->photoMapper->selectPhotoId($externalId);
            if ($photoId !== NULL) {
                return $photoId;
            }

            $this->photoMapper->insertPhotoId($externalId);

            return $this->photoMapper->selectPhotoId($externalId);
        }

        private function getAlbumsResponse(?string $albumId, ?string $pageToken = NULL) : array {
            if ($albumId === NULL) {
                return $this->googleApiClient->getAlbums($pageToken);
            }
            else {
                $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);
                if ($externalAlbumId === NULL) {
                    throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
                }

                $album = $this->googleApiClient->getAlbum($externalAlbumId);
                return array("albums" => array($album));
            }
        }

        private function getPhotosResponse(string $albumId, ?string $pageToken = NULL) : array {
            $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);
            if ($externalAlbumId === NULL) {
                throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
            }

            return $this->googleApiClient->getMediaItems($externalAlbumId, $pageToken);            
        }

        private function createGooglePhotos(string $albumId, array $newPhotos, ?string $replacedPhotoId) : array {
            $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);
            if ($externalAlbumId === NULL) {
                throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
            }

            $externalReplacedPhotoId = NULL;
            if ($replacedPhotoId !== NULL) {
                $externalReplacedPhotoId = $this->photoMapper->selectPhotoExternalId($replacedPhotoId);    
                if ($externalReplacedPhotoId == NULL) {
                    throw new InvalidArgumentException("A photo with the identifier " . $externalReplacedPhotoId . " does not exist.");
                }
            }  
            
            $createdPhotos = $this->googleApiClient->createPhotos($externalAlbumId, $newPhotos, $externalReplacedPhotoId);

            foreach ($createdPhotos as &$createdPhoto) {
                if (isset($createdPhoto["status"]["message"]) && $createdPhoto["status"]["message"] !== "Success") {
                    throw new RuntimeException($createdPhoto["status"]["message"]);
                }
            } 

            return $createdPhotos;
        }

        public function onAllAlbumsInvalidated(mixed $message) : void {
            $this->updateAllAlbums();
        }
        
        public function onAlbumInvalidated(mixed $message) : void {
            $this->updateAlbum($message["albumId"]);
        }

        public function onAlbumUpdated(mixed $message) : void {
            $album = $this->getAlbum($message["albumId"]);
            if ($album !== NULL) {
                $photos = $this->getPhotos($album->getId());
    
                if (count($photos) !== $album->getImagesCount()) {
                    $this->eventPublisher->publishAlbumInvalidatedEvent($album->getId());
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::FETCH_ALBUMS_ACTION_NAME 
                && $message["timeSinceLastExecution"] > self::FETCH_ALBUMS_ACTION_INTERVAL) {
                $this->eventPublisher->publishAllAlbumsInvalidatedEvent();                
                $this->scheduler->recordEventsTriggered(self::FETCH_ALBUMS_ACTION_NAME);
            }
        }
    }
?>