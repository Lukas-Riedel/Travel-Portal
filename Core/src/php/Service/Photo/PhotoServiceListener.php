<?php
    namespace Core\Service\Photo;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class PhotoServiceListener {

        private const PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME = "PhotosUploadingTriggered";
        private const PHOTO_REPLACING_TRIGGERED_EVENT_NAME = "PhotoReplacingTriggered";

        private const FETCH_ALBUMS_ACTION_NAME = "FETCH_ALBUMS";
        private const FETCH_ALBUMS_ACTION_INTERVAL = 6 * CommonConstants::ONE_HOUR_SECONDS;

        private readonly PhotoService $photoService;
        
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(PhotoService $photoService, EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->photoService = $photoService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onAllAlbumsInvalidated(mixed $message) : void {
            $this->photoService->updateAllAlbums();
        }
        
        public function onAlbumInvalidated(mixed $message) : void {
            $this->photoService->updateAlbum($message["albumId"]);
        }

        public function onAlbumUpdated(mixed $message) : void {
            $album = $this->photoService->getAlbum($message["albumId"]);
            if ($album !== null) {
                $photos = $this->photoService->getPhotos($album->getId(), true);
    
                if (count($photos) !== $album->getImagesCount()) {
                    $this->eventPublisher->publish(Event::AlbumInvalidated($album->getId()));
                }
            }
        }

        public function onProcessingFailed(mixed $message) : void {
            if ($message["name"] === self::PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME
                || $message["name"] === self::PHOTO_REPLACING_TRIGGERED_EVENT_NAME) {
                // TODO: Improve by propagating Batch ID instead.
                $this->photoService->removePendingPhotosForAlbum($message["args"]["albumId"]);
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::FETCH_ALBUMS_ACTION_NAME, self::FETCH_ALBUMS_ACTION_INTERVAL)) {
                $this->eventPublisher->publish(Event::AllAlbumsInvalidated());
            }
        }
    }
?>