<?php
    namespace Core\Service\Photo;

    use AurorasLive\SunCalc;
    use Core\Client\Cache\CacheClient;
    use Core\Common\CommonConstants;
    use Core\Service\Place\PlaceIdentifier;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Google\GoogleClient;
    use Core\Service\Place\PlaceIncludedEntity;

    class PhotoService {

        private const PENDING_PHOTOS_EXPIRATION_INTERVAL = CommonConstants::ONE_DAY_SECONDS;
        
        private const ALBUM_PHOTOS_CACHE_KEY_FORMAT = "PhotoService:AlbumPhotos:%s";
        private const ALBUM_PHOTOS_CACHE_TTL = 3000;

        private const ALBUM_THUMBNAIL_WIDTH = 350;
        private const ALBUM_THUMBNAIL_HEIGHT = 233;
        private const ALBUM_THUMBNAIL_CACHE_PATH = "cache/album";

        private readonly PhotoMapper $photoMapper;

        private readonly GoogleClient $googleClient;
        
        private readonly EventPublisher $eventPublisher;
        
        private readonly CacheClient $cacheClient;

        private readonly TransactionManager $transactionManager;

        private readonly string $coreBaseUrl;

        public function __construct(DatabaseClient $databaseClient, GoogleClient $googleClient,
            EventPublisher $eventPublisher, CacheClient $cacheClient, string $coreBaseUrl) {
            $this->photoMapper = new PhotoMapper($databaseClient, $googleClient);
            $this->googleClient = $googleClient;
            $this->eventPublisher = $eventPublisher;
            $this->cacheClient = $cacheClient;
            $this->transactionManager = $databaseClient;
            $this->coreBaseUrl = $coreBaseUrl;
        }

        public function getAllAlbums() : array {
            return $this->photoMapper->selectAllAlbums();            
        }

        public function getReplacedPhotos() : array {
            return $this->photoMapper->selectReplacedPhotos();
        }
        
        public function getAlbum(string $albumId) : ?Album {
            return $this->photoMapper->selectAlbum($albumId);
        }
        
        public function getAlbumForPlaceAndDate(string $placeName, int $timestamp) : ?Album {
            return $this->photoMapper->selectAlbumByName($this->getAlbumName($placeName, $timestamp));
        }
        
        public function getAlbumsForPlace(string $placeName) : array {
            return $this->photoMapper->selectAlbumsForPlaceName($placeName);
        }
        
        public function getAlbumForPhotoId(string $photoId) : ?Album {
            return $this->photoMapper->selectAlbumForPhotoId($photoId);
        }

        public function createAlbum(PlaceIdentifier $placeIdentifier, int $timestamp) : Album {
            $albumName = $this->getAlbumName($placeIdentifier->getName(), $timestamp);
            $createdAlbumExternalId = $this->googleClient->createAlbum($albumName);
            $albumId = $this->getOrCreateAlbumId($createdAlbumExternalId);
            $this->updateAlbum($albumId, $placeIdentifier->getLatitude(), $placeIdentifier->getLongitude());
            return $this->getAlbum($albumId);
        }

        public function updateAllAlbums() : void {            
            $filePaths = $this->doUpdateAlbums(null, false);
            $this->prunePhysicalCache($filePaths);
        }

        public function updateAlbum(string $albumId, ?float $latitude = null, ?float $longitude = null, ?int $mainPhotoPosition = null) : void {            
            $this->createPendingPhotos($albumId);

            if ($mainPhotoPosition !== null) {
                if ($latitude === null || $longitude === null) {
                    throw new \InvalidArgumentException("Latitude and longitude must be provided when setting main photo.");
                }

                $photos = $this->getPhotos($albumId, $latitude, $longitude, true);

                $mainPhotoPosition = $mainPhotoPosition - 1;
                if ($mainPhotoPosition < 0 || $mainPhotoPosition >= count($photos)) {
                    throw new \InvalidArgumentException("Cannot set main photo because there are only " . count($photos) . " photos in the album.");
                }

                $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);       
                if ($externalAlbumId === null) {
                    throw new \InvalidArgumentException("An album with the identifier '$albumId' does not exist.");
                }
    
                $externalPhotoId = $this->photoMapper->selectPhotoExternalId($photos[$mainPhotoPosition]->getId());
                if ($externalPhotoId === null) {
                    throw new \InvalidArgumentException("A photo with the identifier '" . $photos[$mainPhotoPosition]->getId() . "' does not exist.");
                }
    
                $this->googleClient->updateAlbumMainPhoto($externalAlbumId, $externalPhotoId);
            }

            $this->doUpdateAlbums($albumId, true);
        }

        public function getPhoto(string $photoId) : ?Photo {
            return $this->photoMapper->selectPhoto($photoId);
        }

        public function getPhotos(string $albumId, float $latitude, float $longitude, bool $forceRefetch = false) : array {
            $fetchedAlbumKey = sprintf(self::ALBUM_PHOTOS_CACHE_KEY_FORMAT, $albumId);
            if ($forceRefetch) {
                $this->cacheClient->delete($fetchedAlbumKey);
            }

            $cachedPhotos = $this->cacheClient->get($fetchedAlbumKey);
            if ($cachedPhotos !== null) {
                return $cachedPhotos;
            }            

            $photos = array();

            // Fetch photos.
            $response = $this->getPhotosResponse($albumId);
            while (isset($response["mediaItems"] )) {
                foreach ($response["mediaItems"] as &$mediaItem) {
                    $timestamp = strtotime($mediaItem["mediaMetadata"]["creationTime"]);

                    $dateTime = new \DateTime();
                    $dateTime->setTimestamp($timestamp);
                    $suncalc = new SunCalc($dateTime, $latitude, $longitude);
                    $sunPosition = $suncalc->getSunPosition($dateTime);

                    $sunAltitude = $sunPosition->altitude * 180 / M_PI;
                    $sunAzimuth = $sunPosition->azimuth * 180 / M_PI;

                    $photos[] = new Photo(
                        $this->getOrCreatePhotoId($mediaItem["id"]), 
                        fn() => $this->getGooglePhotoProxyUrl($mediaItem["baseUrl"]),
                        $mediaItem["productUrl"],
                        $mediaItem["mediaMetadata"]["photo"]["focalLength"] ?? null,
                        $mediaItem["mediaMetadata"]["photo"]["apertureFNumber"] ?? null,
                        isset($mediaItem["mediaMetadata"]["photo"]["exposureTime"]) ? doubleval(rtrim($mediaItem["mediaMetadata"]["photo"]["exposureTime"], "s")) : null,
                        $mediaItem["mediaMetadata"]["photo"]["isoEquivalent"] ?? null,
                        $timestamp,
                        $sunAltitude,
                        $sunAzimuth
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
            $this->transactionManager->executeAtomically(function() use(&$photos, &$albumId) {
                $deletedPhotosCount = $this->photoMapper->deletePhotos($albumId);        
                foreach ($photos as &$photo) {
                    $this->photoMapper->insertPhoto($photo, $albumId);
                }

                // The count of photos is different from what was previously stored in the database. Invalidate the album.
                if (count($photos) !== $deletedPhotosCount) {
                    $this->eventPublisher->publish(Event::AlbumInvalidated($albumId));
                }
            });

            // Cache photos for faster access in the future.
            $this->cacheClient->set($fetchedAlbumKey, $photos, self::ALBUM_PHOTOS_CACHE_TTL);

            return $photos;
        }

        public function updateAlbumReviewed(string $albumId) : bool {
            return $this->photoMapper->updateAlbumReviewed($albumId);
        }

        public function updateAlbumName(string $albumId, string $oldPlaceName, string $newPlaceName) : bool {
            $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);
            $wasUpdated = $this->googleClient->updateAlbumName($externalAlbumId, str_replace($oldPlaceName, $newPlaceName, $this->getAlbum($albumId)->getName()));
            $this->updateAlbum($albumId);
            return $wasUpdated;
        }

        public function uploadPhoto(string $fileName, string $albumId, string $batchId, int $expectedBatchSize, int $batchPosition, string $data) : PendingPhoto {
            $uploadToken = $this->googleClient->uploadPhoto($data);

            $pendingPhoto = new PendingPhoto(null, $albumId, $fileName, $batchId, $expectedBatchSize, $batchPosition, null, $uploadToken);
            $this->photoMapper->insertPendingPhoto($pendingPhoto, self::PENDING_PHOTOS_EXPIRATION_INTERVAL);
            return $pendingPhoto;
        }

        public function replacePhoto(string $fileName, string $albumId, string $replacedPhotoId, string $data) : PendingPhoto {        
            $uploadToken = $this->googleClient->uploadPhoto($data);

            $pendingPhoto = new PendingPhoto(null, $albumId, $fileName, $fileName, 1, 1, $replacedPhotoId, $uploadToken);
            $this->photoMapper->insertPendingPhoto($pendingPhoto, self::PENDING_PHOTOS_EXPIRATION_INTERVAL);
            return $pendingPhoto;
        }

        public function removePendingPhotosForAlbum(string $albumId) : void {
            $this->photoMapper->deletePendingPhotosForAlbum($albumId);
        }
        
        private function doUpdateAlbums(?string $albumId, bool $overwrite) : array {
            global $highlightService, $placeService;
        
            $filePaths = array();
            $albums = array();
        
            // Fetch albums.
            $response = $this->getAlbumsResponse($albumId);
            while (isset($response["albums"])) {
                foreach ($response["albums"] as &$album) {
                    $mainPhotoId = null;
                    $mainImageUrl = null;

                    if (isset($album["coverPhotoMediaItemId"])) {
                        $fileName = $album["coverPhotoMediaItemId"] . CommonConstants::JPG_FILE_EXTENSION;
                        $filePath = $this->getPhysicalCachePath() . "/" . $fileName;
            
                        if ($overwrite || !file_exists($filePath)) {
                            file_put_contents($filePath, file_get_contents($album["coverPhotoBaseUrl"] 
                                . "=w" . self::ALBUM_THUMBNAIL_WIDTH
                                . "-h" . self::ALBUM_THUMBNAIL_HEIGHT));
                        }
            
                        $filePaths[] = $filePath;
                        $mainImageUrl = $this->coreBaseUrl
                            . "/" . self::ALBUM_THUMBNAIL_CACHE_PATH
                            . "/" . $fileName;
                        
                        $mainPhotoId = $this->getOrCreatePhotoId($album["coverPhotoMediaItemId"]);
                    }
        
                    $imagesCount = 0;
                    if (isset($album["mediaItemsCount"])) {
                        $imagesCount = intval($album["mediaItemsCount"]);
                    }

                    $currentAlbumId = $this->getOrCreateAlbumId($album["id"]);        
                    $albums[] = new Album($currentAlbumId, $album["title"], $mainPhotoId === null ? null : new Photo($mainPhotoId,
                        fn() => $this->getGooglePhotoProxyUrl($album["coverPhotoBaseUrl"]), null, null, null, null, null, null, null, null),
                        $mainImageUrl, $album["productUrl"], $imagesCount, 0, false, null, null);

                    // TODO: This is temporary until there is proper support for highlights (Q3/2025).
                    // Remove global variables when removing this code.
                    if ($albumId !== null && isset($album["coverPhotoMediaItemId"])) {
                        $places = $placeService->getRegularPlaces(null, null, null, null, $currentAlbumId, null, null, null, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
                        foreach ($places as &$place) {
                            $highlightService->createPlaceHighlight($place->getId(), $mainPhotoId);
                            foreach ($place->getDates() as &$date) {
                                if ($date->getTrip() !== null) {
                                    $highlightService->createTripHighlight($date->getTrip()->getId(), $mainPhotoId);
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
            $this->transactionManager->executeAtomically(function() use(&$albumId, &$albums) {
                $this->photoMapper->deleteAlbums($albumId);        
                foreach ($albums as &$album) {
                    $this->photoMapper->insertAlbum($album);
                }

                // Trigger an event for updated albums.
                // As of now, the event is only triggered if the count of photos in the album has changed.
                if ($albumId === null) {
                    $changedAlbumIds = $this->photoMapper->selectAlbumIdsWithOutdatedPhotos();
                    foreach ($changedAlbumIds as &$changedAlbumId) {
                        $this->eventPublisher->publish(Event::AlbumUpdated($changedAlbumId));
                    }
                }
                else {
                    $this->eventPublisher->publish(Event::AlbumUpdated($albumId));
                }
            });

            // Final clean-up.
            $this->photoMapper->deleteStaleAlbumIdentifiers();
            $this->photoMapper->deleteStalePhotoIdentifiers();
            $this->photoMapper->deleteStalePendingPhotos();

            return $filePaths;
        }
        
        private function createPendingPhotos(string $albumId) : void {            
            // Process pending photos with fixed position.
            $pendingPhotos = $this->photoMapper->selectPendingPhotosWithFixedPosition($albumId);        
            while (count($pendingPhotos) > 0) {
                $this->transactionManager->executeAtomically(function() use(&$albumId, &$pendingPhotos) {
                    $newPhotos = array();
                    foreach ($pendingPhotos as &$pendingPhoto) {
                        $newPhotos[] = array(
                            "uploadToken" => $pendingPhoto->getUploadToken(),
                            "fileName" => $pendingPhoto->getFileName()
                        );
                        $this->photoMapper->deletePendingPhoto($pendingPhoto->getId());
                    }

                    $this->createGooglePhotos($albumId, $newPhotos, null);                      
                });

                $pendingPhotos = $this->photoMapper->selectPendingPhotosWithFixedPosition($albumId);
            }
                   
            // Process pending photos with relative position.
            $pendingPhotos = $this->photoMapper->selectPendingPhotosWithRelativePosition($albumId);            
            foreach ($pendingPhotos as &$pendingPhoto) {
                $newPhoto = array(
                    "uploadToken" => $pendingPhoto->getUploadToken(),
                    "fileName" => $pendingPhoto->getFileName()
                );

                $oldPhotoExternalId = $this->photoMapper->selectPhotoExternalId($pendingPhoto->getReplacedPhotoId());
                $albumExternalId = $this->photoMapper->selectAlbumExternalId($albumId);

                $this->transactionManager->executeAtomically(function() use(&$albumId, &$albumExternalId, &$newPhoto, &$oldPhotoExternalId, &$pendingPhoto) {
                    $this->photoMapper->deletePendingPhoto($pendingPhoto->getId());
                    $createdPhotoExternalId = $this->createGooglePhotos($albumId, array($newPhoto), $pendingPhoto->getReplacedPhotoId())["newMediaItemResults"][0]["mediaItem"]["id"];

                    $this->photoMapper->updatePhotoExternalId($pendingPhoto->getReplacedPhotoId(), $createdPhotoExternalId);                
                    if ($this->getAlbum($albumId)?->getMainPhoto()?->getId() == $pendingPhoto->getReplacedPhotoId()) {
                        $this->googleClient->updateAlbumMainPhoto($albumExternalId, $createdPhotoExternalId);
                    }
                    
                    $oldPhotoNewId = $this->getOrCreatePhotoId($oldPhotoExternalId, true);
                    $this->photoMapper->insertPhoto($this->getPhoto($pendingPhoto->getReplacedPhotoId())->withReplacedId($oldPhotoNewId), $albumId);

                    $this->eventPublisher->publish(Event::PhotoInvalidated($pendingPhoto->getReplacedPhotoId()));
                });
            }
        }

        private function getPhysicalCachePath() : string {
            return __DIR__ . "/../../../" . self::ALBUM_THUMBNAIL_CACHE_PATH;
        }

        private function prunePhysicalCache(array $usedFilePaths) : void {
            $existingFilePaths = array_filter((array) glob($this->getPhysicalCachePath() . "/*"));
            $unusedFilePaths = array_diff($existingFilePaths, $usedFilePaths);    
            array_map("unlink", $unusedFilePaths);
        }

        private function getAlbumName(string $placeName, int $timestamp) : string {
            return $placeName . " " . date(CommonConstants::DMY_DATE_FORMAT, $timestamp);
        }
        
        private function getOrCreateAlbumId(string $externalId) : string {
            $albumId = $this->photoMapper->selectAlbumId($externalId);
            if ($albumId !== null) {
                return $albumId;
            }

            $this->photoMapper->insertAlbumId($externalId);

            return $this->photoMapper->selectAlbumId($externalId);
        }
    
        private function getOrCreatePhotoId(string $externalId, bool $replaced = false) : string {
            $photoId = $this->photoMapper->selectPhotoId($externalId);
            if ($photoId !== null) {
                return $photoId;
            }

            $this->photoMapper->insertPhotoId($externalId, $replaced);

            return $this->photoMapper->selectPhotoId($externalId);
        }

        private function getAlbumsResponse(?string $albumId, ?string $pageToken = null) : array {
            if ($albumId === null) {
                return $this->googleClient->getAlbums($pageToken);
            }
            else {
                $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);
                if ($externalAlbumId === null) {
                    throw new \InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
                }

                $album = $this->googleClient->getAlbum($externalAlbumId);
                return array("albums" => array($album));
            }
        }

        private function getPhotosResponse(string $albumId, ?string $pageToken = null) : array {
            $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);
            if ($externalAlbumId === null) {
                throw new \InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
            }

            return $this->googleClient->getPhotos($externalAlbumId, $pageToken);            
        }

        private function createGooglePhotos(string $albumId, array $newPhotos, ?string $replacedPhotoId) : array {
            $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);
            if ($externalAlbumId === null) {
                throw new \InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
            }

            $externalReplacedPhotoId = null;
            if ($replacedPhotoId !== null) {
                $externalReplacedPhotoId = $this->photoMapper->selectPhotoExternalId($replacedPhotoId);    
                if ($externalReplacedPhotoId == null) {
                    throw new \InvalidArgumentException("A photo with the identifier " . $externalReplacedPhotoId . " does not exist.");
                }
            }  
            
            $createdPhotos = $this->googleClient->createPhotos($externalAlbumId, $newPhotos, $externalReplacedPhotoId);

            foreach ($createdPhotos["newMediaItemResults"] as &$createdPhoto) {
                if (isset($createdPhoto["status"]["message"]) && $createdPhoto["status"]["message"] !== "Success") {
                    throw new \RuntimeException($createdPhoto["status"]["message"]);
                }
            } 

            return $createdPhotos;
        }

        private function getGooglePhotoProxyUrl(string $url) : string {
            return str_replace(CommonConstants::GOOGLE_USER_CONTENT_BASE_URL, $this->coreBaseUrl . CommonConstants::GOOGLE_USER_CONTENT_PROXY_BASE_URL, $url);
        }
    }
?>