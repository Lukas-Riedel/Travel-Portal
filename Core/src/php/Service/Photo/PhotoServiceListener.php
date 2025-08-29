<?php
    namespace Core\Service\Photo;

    use Core\Common\CommonConstants;

    class PhotoServiceListener {

        private const FETCH_ALBUMS_ACTION_NAME = "FETCH_ALBUMS";
        private const FETCH_ALBUMS_ACTION_INTERVAL = 6 * CommonConstants::ONE_HOUR_SECONDS;

        private readonly PhotoService $photoService;
        
        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(PhotoService $photoService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
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
                    $this->eventPublisher->publishAlbumInvalidatedEvent($album->getId());
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::FETCH_ALBUMS_ACTION_NAME, self::FETCH_ALBUMS_ACTION_INTERVAL)) {
                $this->eventPublisher->publishAllAlbumsInvalidatedEvent();
            }
        }
    }
?>