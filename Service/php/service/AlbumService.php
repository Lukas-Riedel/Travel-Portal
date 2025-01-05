<?php
    require_once(dirname(__FILE__) . "/AlbumMapper.php");
    require_once(dirname(__FILE__) . "/../model/Album.php");

    class AlbumService {

        private const FETCH_ALBUMS_ACTION_NAME = "FETCH_ALBUMS";
        private const FETCH_ALBUMS_ACTION_INTERVAL = 21600;

        private const JPG_FILE_EXTENSION = ".jpg";

        private readonly AlbumMapper $albumMapper;

        private readonly GoogleApiClient $googleApiClient;

        private readonly ConfigurationService $configurationService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        
        public function __construct(DatabaseProvider $databaseProvider, GoogleApiClient $googleApiClient, 
            ConfigurationService $configurationService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->albumMapper = new AlbumMapper($databaseProvider);
            $this->googleApiClient = $googleApiClient;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }
        
        public function getAlbum(string $albumId) : ?Album {
            return $this->albumMapper->selectAlbum($albumId);
        }

        public function getExternalId(string $albumId) : ?string {
            return $this->albumMapper->selectExternalId($albumId);
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
            global $photoService;
            
            $photoService->createPendingPhotos($albumId);

            if ($mainPhotoPosition !== NULL) {
                $photos = $photoService->getPhotos($albumId);

                $mainPhotoPosition = $mainPhotoPosition - 1;
                if ($mainPhotoPosition < 0 || $mainPhotoPosition >= count($photos)) {
                    throw new RuntimeException("Cannot set main photo because there are only " . count($photos) . " photos in the album.");
                }

                $externalAlbumId = $this->getExternalId($albumId);       
                if ($externalAlbumId === NULL) {
                    throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
                }
    
                $externalPhotoId = $photoService->getExternalId($photos[$mainPhotoPosition]->getId());
                if ($externalPhotoId === NULL) {
                    throw new InvalidArgumentException("A photo with the identifier " . $photos[$mainPhotoPosition]->getId() . " does not exist.");
                }
    
                $this->googleApiClient->updateAlbumMainPhoto($externalAlbumId, $externalPhotoId);
            }

            $this->doUpdateAlbums($albumId, TRUE);
        }

        public function onAllAlbumsInvalidated(mixed $message) : void {
            $this->updateAllAlbums();
        }
        
        public function onAlbumInvalidated(mixed $message) : void {
            $this->updateAlbum($message["albumId"]);
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::FETCH_ALBUMS_ACTION_NAME 
                && $message["timeSinceLastExecution"] > self::FETCH_ALBUMS_ACTION_INTERVAL) {
                $this->eventPublisher->publishAllAlbumsInvalidatedEvent();                
                $this->scheduler->recordEventsTriggered(self::FETCH_ALBUMS_ACTION_NAME);
            }
        }
        
        private function doUpdateAlbums(?string $albumId, bool $forceOverwrite) : array {
            global $photoService, $highlightService, $databaseProvider;
        
            $filePaths = array();
            $albums = array();
        
            // Fetch albums.
            $response = $this->getGooglePhotosAlbumResponse($albumId);
            while (isset($response["albums"])) {
                foreach ($response["albums"] as &$album) {
                    $mainPhotoId = NULL;
                    $mainImageUrl = NULL;

                    if (isset($album["coverPhotoMediaItemId"])) {
                        $fileName = $album["coverPhotoMediaItemId"] . self::JPG_FILE_EXTENSION;
                        $filePath = $this->getPhysicalCachePath() . "/" . $fileName;
            
                        if ($forceOverwrite || !file_exists($filePath)) {
                            file_put_contents($filePath, file_get_contents($album["coverPhotoBaseUrl"] 
                                . "=w" . $this->configurationService->getConfigurationForTypeAndKey("albumThumbnailImageSize", "width")
                                . "-h" . $this->configurationService->getConfigurationForTypeAndKey("albumThumbnailImageSize", "height")));
                        }
            
                        $filePaths[] = $filePath;
                        $mainImageUrl = $this->configurationService->getBaseUrl() 
                            . "/" . $this->configurationService->getConfigurationForTypeAndKey("cachePath", "albumThumbnail")
                            . "/" . $fileName;
                        
                        $mainPhotoId = $photoService->getOrCreatePhotoIdentifier($album["coverPhotoMediaItemId"]);
                    }
        
                    $imagesCount = 0;
                    if (isset($album["mediaItemsCount"])) {
                        $imagesCount = intval($album["mediaItemsCount"]);
                    }

                    $currentAlbumId = $this->getOrCreateAlbumId($album["id"]);        
                    $albums[] = new Album($currentAlbumId, $album["title"], $mainPhotoId, $mainImageUrl, $album["productUrl"], $imagesCount, 0);

                    // TODO: This is temporary until there is proper support for highlights (Q1/2025).
                    // Remove $highlightService and $databaseProvider from global variables when removing this code.
                    if (isset($album["coverPhotoMediaItemId"])) {
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
                                $highlightService->createCategoryHighlight($categoryId, $mainPhotoId);
                            }
                        }
                    }  
                    // End of temporary code.                          
                }
        
                if (isset($response["nextPageToken"])) {
                    $response = $this->getGooglePhotosAlbumResponse($albumId, $response["nextPageToken"]);
                }
                else {
                    $response = array();
                }
            }
        
            // Persist albums.
            $this->albumMapper->deleteAlbums($albumId);        
            foreach ($albums as &$album) {
                $this->albumMapper->insertAlbum($album);
            }

            // Trigger an event for updated albums.
            // As of now, the event is only triggered if the count of photos in the album has changed.
            if ($albumId === NULL) {
                $changedAlbumIds = $this->albumMapper->selectAlbumIdsWithOutdatedPhotos();
                foreach ($changedAlbumIds as &$changedAlbumId) {
                    $this->eventPublisher->publishAlbumUpdatedEvent($changedAlbumId);
                }
            }
            else {
                $this->eventPublisher->publishAlbumUpdatedEvent($albumId);
            }

            return $filePaths;
        }

        private function getPhysicalCachePath() : string {
            return dirname(__FILE__) . "/../../" . $this->configurationService->getConfigurationForTypeAndKey("cachePath", "albumThumbnail");
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
            $albumId = $this->albumMapper->selectAlbumId($externalId);
            if ($albumId !== NULL) {
                return $albumId;
            }

            $this->albumMapper->insertAlbumId($externalId);

            return $this->albumMapper->selectAlbumId($externalId);
        }
    
        private function getGooglePhotosAlbumResponse(?string $albumId, ?string $pageToken = NULL) : array {
            if ($albumId === NULL) {
                return $this->googleApiClient->getAlbums($pageToken);
            }
            else {
                $externalAlbumId = $this->getExternalId($albumId);
                if ($externalAlbumId === NULL) {
                    throw new InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
                }

                $album = $this->googleApiClient->getAlbum($externalAlbumId);
                return array("albums" => array($album));
            }
        }
    }
?>