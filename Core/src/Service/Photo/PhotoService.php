<?php
    namespace Core\Service\Photo;

    use AurorasLive\SunCalc;
    use Common\Client\Http\HttpMethod;
    use Common\Client\Cache\CacheClient;
    use Core\Client\CloudStorage\CloudStorageClient;
    use Core\Common\CommonConstants;
    use Core\Service\Place\PlaceIdentifier;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Client\Google\GoogleClient;
    use Core\Client\Http\HttpClient;
    use Core\Service\Embedding\EmbeddingService;

    class PhotoService {

        private const PENDING_PHOTOS_EXPIRATION_INTERVAL = CommonConstants::ONE_HOUR_SECONDS;
        
        private const ALBUM_PHOTOS_CACHE_KEY_FORMAT = "PhotoService:AlbumPhotos:%s";
        private const ALBUM_PHOTOS_CACHE_TTL = 1800;

        private const PENDING_PHOTO_EMBEDDING_CACHE_KEY_FORMAT = "PhotoService:PendingPhotos:Embeddings:%s";
        private const PENDING_PHOTO_EMBEDDING_CACHE_TTL = self::PENDING_PHOTOS_EXPIRATION_INTERVAL;

        private const PHOTO_EMBEDDING_CACHE_KEY_FORMAT = "PhotoService:AlbumPhotos:Embeddings:%s";
        private const PHOTO_EMBEDDING_CACHE_TTL = CommonConstants::ONE_DAY_SECONDS;

        private readonly PhotoMapper $photoMapper;
        private readonly EmbeddingService $embeddingService;
        private readonly GoogleClient $googleClient;        
        private readonly EventPublisher $eventPublisher;
        private readonly CloudStorageClient $cloudStorageClient;        
        private readonly CacheClient $distributedCacheClient;
        private readonly HttpClient $httpClient;
        private readonly TransactionManager $transactionManager;

        private readonly string $coreBaseUrl;
        private readonly string $albumThumbnailBucket;
        private readonly int $thumbnailWidth;
        private readonly int $thumbnailHeight;
        private readonly int $embeddingWidth;
        private readonly int $embeddingHeight;

        public function __construct(DatabaseClient $databaseClient, EmbeddingService $embeddingService, GoogleClient $googleClient,
            EventPublisher $eventPublisher, CloudStorageClient $cloudStorageClient, CacheClient $distributedCacheClient,
            HttpClient $httpClient, string $coreBaseUrl, string $albumThumbnailBucket, int $thumbnailWidth, int $thumbnailHeight,
            int $embeddingWidth, int $embeddingHeight, int $indoorPhotoIsoThreshold) {
            $this->photoMapper = new PhotoMapper($databaseClient, $googleClient, $indoorPhotoIsoThreshold);
            $this->embeddingService = $embeddingService;
            $this->googleClient = $googleClient;
            $this->eventPublisher = $eventPublisher;
            $this->cloudStorageClient = $cloudStorageClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->transactionManager = $databaseClient;
            $this->httpClient = $httpClient;
            $this->coreBaseUrl = $coreBaseUrl;
            $this->albumThumbnailBucket = $albumThumbnailBucket;
            $this->thumbnailWidth = $thumbnailWidth;
            $this->thumbnailHeight = $thumbnailHeight;
            $this->embeddingWidth = $embeddingWidth;
            $this->embeddingHeight = $embeddingHeight;
        }

        public function getAllAlbums() : array {
            return $this->photoMapper->selectAllAlbums();            
        }

        public function getReplacedPhotos() : array {
            return $this->photoMapper->selectReplacedPhotos();
        }

        public function getPhotoEmbeddings(string $albumId) : array {
            return $this->photoMapper->selectPhotoEmbeddings($albumId);
        }

        public function getPhotoEmbedding(string $photoId) : ?PhotoEmbedding {
            return $this->photoMapper->selectPhotoEmbedding($photoId);
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
            $objectKeys = $this->doUpdateAlbums(null, false);
            $this->pruneUnusedObjects($objectKeys);
        }

        public function updateAlbum(string $albumId, ?float $latitude = null, ?float $longitude = null, ?int $mainPhotoPosition = null, ?string $batchId = null) : void {            
            if ($batchId !== null) {
                $this->createPendingPhotos($albumId, $batchId);
            }

            if ($mainPhotoPosition !== null) {
                if ($latitude === null || $longitude === null) {
                    throw new \InvalidArgumentException("Latitude and longitude must be provided when setting main photo.");
                }

                $photos = $this->getPhotosForAlbum($albumId, $latitude, $longitude, true);

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
    
                try {
                    $this->googleClient->updateAlbumMainPhoto($externalAlbumId, $externalPhotoId);
                }
                catch (\Throwable $e) {
                    // Avoid getting quota exceeded for concurrent writes, retry after a few seconds.
                    sleep(5);
                    $this->googleClient->updateAlbumMainPhoto($externalAlbumId, $externalPhotoId);
                }
            }

            $this->doUpdateAlbums($albumId, true);
        }

        public function getUsedCameras() : array {
            return $this->photoMapper->selectUsedCameras();
        }

        public function getPhotosCountForCamera(string $camera, int $start, int $end) : int {
            return $this->photoMapper->selectPhotosCountForcamera($camera, $start, $end);
        }

        public function getPhoto(string $photoId) : ?Photo {
            return $this->photoMapper->selectPhoto($photoId);
        }

        public function getPhotosByIds(array $photoIds) : array {
            return $this->photoMapper->selectPhotos($photoIds);
        }

        public function getPhotosForAlbum(string $albumId, float $latitude, float $longitude, bool $forceRefetch = false) : array {
            $fetchedAlbumKey = sprintf(self::ALBUM_PHOTOS_CACHE_KEY_FORMAT, $albumId);
            if ($forceRefetch) {
                $this->distributedCacheClient->delete($fetchedAlbumKey);
            }

            $cachedPhotos = $this->distributedCacheClient->get($fetchedAlbumKey);
            if ($cachedPhotos !== null) {
                if (count($cachedPhotos) === 0) {
                    return $cachedPhotos;
                }
                
                $photo = $cachedPhotos[0];
                if ($this->httpClient->returns2xx(HttpMethod::HEAD, $photo["url"])) {
                    return $cachedPhotos;
                }
                
                $this->distributedCacheClient->delete($fetchedAlbumKey);
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
                        $this->getOrCreatePhotoId($mediaItem["id"], false, $mediaItem["baseUrl"]), 
                        fn() => $this->getGooglePhotoProxyUrl($mediaItem["baseUrl"]),
                        $mediaItem["productUrl"],
                        implode(" ", array_filter(array($mediaItem["mediaMetadata"]["photo"]["cameraMake"] ?? null, $mediaItem["mediaMetadata"]["photo"]["cameraModel"] ?? null))) ?: null,
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
            $this->distributedCacheClient->set($fetchedAlbumKey, $photos, self::ALBUM_PHOTOS_CACHE_TTL);

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
            $embedding = $this->embeddingService->getPhotoEmbedding($data);
            if ($embedding === null) {
                throw new \RuntimeException("Failed to get embedding for the uploaded photo.");
            }

            $this->distributedCacheClient->set($this->getPendingPhotoEmbeddingCacheKey($uploadToken), $embedding, self::PENDING_PHOTO_EMBEDDING_CACHE_TTL);

            $pendingPhoto = new PendingPhoto(null, $albumId, $fileName, $batchId, $expectedBatchSize, $batchPosition, null, $uploadToken);
            $this->photoMapper->insertPendingPhoto($pendingPhoto, self::PENDING_PHOTOS_EXPIRATION_INTERVAL);
            return $pendingPhoto;
        }

        public function replacePhoto(string $fileName, string $albumId, string $replacedPhotoId, string $data) : PendingPhoto {        
            $uploadToken = $this->googleClient->uploadPhoto($data);
            $embedding = $this->embeddingService->getPhotoEmbedding($data);
            if ($embedding === null) {
                throw new \RuntimeException("Failed to get embedding for the uploaded photo.");
            }

            $this->distributedCacheClient->set($this->getPendingPhotoEmbeddingCacheKey($uploadToken), $embedding, self::PENDING_PHOTO_EMBEDDING_CACHE_TTL);

            $pendingPhoto = new PendingPhoto(null, $albumId, $fileName, $replacedPhotoId, 1, 1, $replacedPhotoId, $uploadToken);
            $this->photoMapper->insertPendingPhoto($pendingPhoto, self::PENDING_PHOTOS_EXPIRATION_INTERVAL);
            return $pendingPhoto;
        }

        public function removePendingPhotosForAlbum(string $albumId) : void {
            $this->photoMapper->deletePendingPhotosForAlbum($albumId);
        }
        
        private function doUpdateAlbums(?string $albumId, bool $overwrite) : array {        
            $objectKeys = array();
            $existingObjectKeys = $this->cloudStorageClient->list($this->albumThumbnailBucket);
            $existingKeysMap = array_flip($existingObjectKeys);

            $albums = array();
        
            // Fetch albums.
            $response = $this->getAlbumsResponse($albumId);
            if ($response === null && $albumId !== null) {
                $this->photoMapper->deleteAlbums($albumId);
            }

            while ($response !== null && isset($response["albums"])) {
                foreach ($response["albums"] as &$album) {
                    $mainPhotoId = null;
                    $mainImageUrl = null;

                    if (isset($album["coverPhotoMediaItemId"])) {
                        $objectKey = $album["coverPhotoMediaItemId"] . CommonConstants::JPG_FILE_EXTENSION;
            
                        if ($overwrite || !isset($existingKeysMap[$objectKey])) {
                            $data = $this->httpClient->executeRequest(HttpMethod::GET,
                                $album["coverPhotoBaseUrl"] . "=w" . $this->thumbnailWidth . "-h" . $this->thumbnailHeight);
                            $this->cloudStorageClient->put($this->albumThumbnailBucket, $objectKey, $data);
                        }

                        $objectKeys[] = $objectKey;
                        $mainImageUrl = $this->cloudStorageClient->getPath($this->albumThumbnailBucket, $objectKey);
                        
                        $mainPhotoId = $this->getOrCreatePhotoId($album["coverPhotoMediaItemId"], false, $album["coverPhotoBaseUrl"]);
                    }
        
                    $imagesCount = 0;
                    if (isset($album["mediaItemsCount"])) {
                        $imagesCount = intval($album["mediaItemsCount"]);
                    }

                    $currentAlbumId = $this->getOrCreateAlbumId($album["id"]);        
                    $albums[] = new Album($currentAlbumId, $album["title"], $mainPhotoId === null ? null : new Photo($mainPhotoId,
                        fn() => $this->getGooglePhotoProxyUrl($album["coverPhotoBaseUrl"]), null, null, null, null, null, null, null, null, null),
                        $mainImageUrl, $album["productUrl"], $imagesCount, 0, false, null, null);                         
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

            return $objectKeys;
        }
        
        private function createPendingPhotos(string $albumId, string $batchId) : void {            
            // Process pending photos with fixed position.
            $pendingPhotos = $this->photoMapper->selectPendingPhotosWithFixedPosition($albumId, $batchId);        
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

                $pendingPhotos = $this->photoMapper->selectPendingPhotosWithFixedPosition($albumId, $batchId);
            }
                   
            // Process pending photos with relative position.
            $pendingPhotos = $this->photoMapper->selectPendingPhotosWithRelativePosition($albumId, $batchId);            
            foreach ($pendingPhotos as &$pendingPhoto) {
                $newPhoto = array(
                    "uploadToken" => $pendingPhoto->getUploadToken(),
                    "fileName" => $pendingPhoto->getFileName()
                );

                $oldPhotoExternalId = $this->photoMapper->selectPhotoExternalId($pendingPhoto->getReplacedPhotoId());
                $oldPhotoEmbedding = $this->getPhotoEmbedding($pendingPhoto->getReplacedPhotoId())?->getEmbedding();
                $albumExternalId = $this->photoMapper->selectAlbumExternalId($albumId);

                $this->transactionManager->executeAtomically(function() use(&$albumId, &$albumExternalId, &$oldPhotoEmbedding, &$newPhoto, &$oldPhotoExternalId, &$pendingPhoto) {
                    $this->photoMapper->deletePendingPhoto($pendingPhoto->getId());
                    $createdPhoto = $this->createGooglePhotos($albumId, array($newPhoto), $pendingPhoto->getReplacedPhotoId())["newMediaItemResults"][0]["mediaItem"];

                    $this->photoMapper->updatePhotoExternalId($pendingPhoto->getReplacedPhotoId(), $createdPhoto["id"]);
                    $this->photoMapper->updatePhotoEmbedding($pendingPhoto->getReplacedPhotoId(), $this->distributedCacheClient->get($this->getPhotoEmbeddingCacheKey($createdPhoto["id"])));                
                    if ($this->getAlbum($albumId)?->getMainPhoto()?->getId() == $pendingPhoto->getReplacedPhotoId()) {
                        $this->googleClient->updateAlbumMainPhoto($albumExternalId, $createdPhoto["id"]);
                    }
                    
                    $oldPhotoNewId = $this->getOrCreatePhotoId($oldPhotoExternalId, true, $oldPhotoEmbedding);
                    $this->photoMapper->insertPhoto($this->getPhoto($pendingPhoto->getReplacedPhotoId())->withReplacedId($oldPhotoNewId), $albumId);

                    $this->eventPublisher->publish(Event::PhotoInvalidated($pendingPhoto->getReplacedPhotoId()));
                });
            }
        }

        private function pruneUnusedObjects(array $usedObjectKeys) : void {
            $existingObjectKeys = $this->cloudStorageClient->list($this->albumThumbnailBucket);
            $unusedObjectKeys = array_diff($existingObjectKeys, $usedObjectKeys);
            foreach ($unusedObjectKeys as $objectKey) {
                $this->cloudStorageClient->delete($this->albumThumbnailBucket, $objectKey);
            }
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
    
        private function getOrCreatePhotoId(string $externalId, bool $replaced, string | array $baseUrlOrEmbedding) : string {
            $photoId = $this->photoMapper->selectPhotoId($externalId);
            if ($photoId !== null) {                
                return $photoId;
            }

            $embedding = $this->distributedCacheClient->get($this->getPhotoEmbeddingCacheKey($externalId));
            if ($embedding === null) {
                $embedding = is_string($baseUrlOrEmbedding) ? $this->fetchPhotoEmbedding($baseUrlOrEmbedding) : $baseUrlOrEmbedding;
            }

            $this->photoMapper->insertPhotoId($externalId, $replaced, $embedding);

            return $this->photoMapper->selectPhotoId($externalId);
        }

        private function fetchPhotoEmbedding(string $baseUrl) : array {
            $url = $baseUrl . "=w" . $this->embeddingWidth . "-h" . $this->embeddingHeight;
            $data = $this->httpClient->executeRequest(HttpMethod::GET, $url);
            return $this->embeddingService->getPhotoEmbedding(base64_encode($data));            
        }

        private function getAlbumsResponse(?string $albumId, ?string $pageToken = null) : ?array {
            if ($albumId === null) {
                return $this->googleClient->getAlbums($pageToken);
            }
            else {
                $externalAlbumId = $this->photoMapper->selectAlbumExternalId($albumId);
                if ($externalAlbumId === null) {
                    throw new \InvalidArgumentException("An album with the identifier " . $albumId . " does not exist.");
                }

                try {
                    $album = $this->googleClient->getAlbum($externalAlbumId);
                }
                catch (\Throwable $e) {
                    return null;
                }

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

                $pendingPhotoEmbeddingCacheKey = $this->getPendingPhotoEmbeddingCacheKey($createdPhoto["uploadToken"]);
                $pendingPhotoEmbedding = $this->distributedCacheClient->get($pendingPhotoEmbeddingCacheKey);
                if ($pendingPhotoEmbedding !== null) {
                    $this->distributedCacheClient->set($this->getPhotoEmbeddingCacheKey($createdPhoto["mediaItem"]["id"]), $pendingPhotoEmbedding, self::PHOTO_EMBEDDING_CACHE_TTL);
                    $this->distributedCacheClient->delete($pendingPhotoEmbeddingCacheKey);
                }
            } 

            return $createdPhotos;
        }

        private function getGooglePhotoProxyUrl(string $url) : string {
            return str_replace(CommonConstants::GOOGLE_USER_CONTENT_BASE_URL, $this->coreBaseUrl . CommonConstants::GOOGLE_USER_CONTENT_PROXY_BASE_URL, $url);
        }

        private function getPendingPhotoEmbeddingCacheKey(string $uploadToken) : string {
            // TODO: Why is this not in the photo_pending table?
            return sprintf(self::PENDING_PHOTO_EMBEDDING_CACHE_KEY_FORMAT, $uploadToken);
        }

        private function getPhotoEmbeddingCacheKey(string $externalId) : string {
            return sprintf(self::PHOTO_EMBEDDING_CACHE_KEY_FORMAT, $externalId);
        }
    }
?>