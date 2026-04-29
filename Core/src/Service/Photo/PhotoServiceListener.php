<?php
    namespace Core\Service\Photo;

    use Common\Client\Cache\CacheClient;
    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Place\PlaceService;

    class PhotoServiceListener {

        private const PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME = "PhotosUploadingTriggered";
        private const PHOTO_REPLACING_TRIGGERED_EVENT_NAME = "PhotoReplacingTriggered";
        
        private const UPLOADING_COMPLETING_LOCK_FORMAT = "PhotoServiceListener:Lock:UploadingCompleting:%s";
        private const UPLOADING_COMPLETING_LOCK_TTL = 900;

        private const FETCH_ALBUMS_ACTION_NAME = "FETCH_ALBUMS";
        private const FETCH_ALBUMS_ACTION_INTERVAL = 6 * CommonConstants::ONE_HOUR_SECONDS;

        private readonly PhotoService $photoService;
        private readonly PlaceService $placeService;    
        private readonly CacheClient $distributedCacheClient;    
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(PhotoService $photoService, PlaceService $placeService, CacheClient $distributedCacheClient, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->photoService = $photoService;
            $this->placeService = $placeService;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onAllAlbumsInvalidated(mixed $message) : void {
            $this->photoService->updateAllAlbums();
        }
        
        public function onAlbumInvalidated(mixed $message) : void {
            $this->photoService->updateAlbum($message["albumId"]);
        }

        public function onPhotoUploadingTriggered(mixed $message) : void {
            $this->photoService->uploadPhoto($message["fileName"], $message["albumId"], $message["batchId"], $message["expectedBatchSize"], $message["batchPosition"], $message["data"]);

            $this->distributedCacheClient->lock(sprintf(self::UPLOADING_COMPLETING_LOCK_FORMAT, $message["albumId"]), self::UPLOADING_COMPLETING_LOCK_TTL, function() use(&$message) {
                if ($this->photoService->getPendingPhotosCount($message["albumId"], $message["batchId"]) === $message["expectedBatchSize"]) {
                    $place = $this->placeService->getRegularPlaceForAlbum($message["albumId"]);
                    $this->photoService->updateAlbum($message["albumId"], $place?->getLatitude(), $place?->getLongitude(), $message["albumMainPhotoPosition"], $message["batchId"]);
                    // TODO: This event is published just to end the processing in Agent. Is this really the best solution? Shouldn't we just end the processing here? What if the code above fails and this event is not published?
                    $this->eventPublisher->publish(Event::PhotosUploadingCompleted($message["agentId"], $message["batchId"]));
                }                
            });
        }

        public function onAlbumUpdated(mixed $message) : void {
            $album = $this->photoService->getAlbum($message["albumId"]);
            if ($album !== null) {
                $place = $this->placeService->getRegularPlaceForAlbum($message["albumId"]);
                $photos = $this->photoService->getPhotosForAlbum($album->getId(), $place?->getLatitude(), $place?->getLongitude(), true);
    
                if (count($photos) !== $album->getImagesCount()) {
                    $this->eventPublisher->publish(Event::AlbumInvalidated($album->getId()));
                }
            }
        }

        public function onProcessingFailed(mixed $message) : void {
            if ($message["name"] === self::PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME
                || $message["name"] === self::PHOTO_REPLACING_TRIGGERED_EVENT_NAME) {
                if (isset($message["args"]["albumId"])) {
                    // TODO: Improve by propagating Batch ID instead.
                    $this->photoService->removePendingPhotosForAlbum($message["args"]["albumId"]);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::FETCH_ALBUMS_ACTION_NAME, self::FETCH_ALBUMS_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::AllAlbumsInvalidated());
            }
        }
    }
?>