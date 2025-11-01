<?php
    namespace Core\Service\Photo;

    use Core\Common\CommonConstants;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Place\PlaceService;

    class PhotoServiceListener {

        private const PHOTOS_UPLOADING_TRIGGERED_EVENT_NAME = "PhotosUploadingTriggered";
        private const PHOTO_REPLACING_TRIGGERED_EVENT_NAME = "PhotoReplacingTriggered";

        private const FETCH_ALBUMS_ACTION_NAME = "FETCH_ALBUMS";
        private const FETCH_ALBUMS_ACTION_INTERVAL = 6 * CommonConstants::ONE_HOUR_SECONDS;

        private readonly PhotoService $photoService;

        private readonly PlaceService $placeService;

        private readonly HighlightService $highlightService;
        
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(PhotoService $photoService, PlaceService $placeService, HighlightService $highlightService,
            EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->photoService = $photoService;
            $this->placeService = $placeService;
            $this->highlightService = $highlightService;
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
                $place = $this->placeService->getRegularPlaceForAlbum($message["albumId"]);
                $photos = $this->photoService->getPhotos($album->getId(), $place?->getLatitude(), $place?->getLongitude(), true);
    
                if (count($photos) !== $album->getImagesCount()) {
                    $this->eventPublisher->publish(Event::AlbumInvalidated($album->getId()));
                }

                if ($place !== null && count($photos) > 0) {
                    if ($place->getMainHighlight() === null) {
                        $this->highlightService->createPlaceHighlight($place->getId(), $album->getMainPhoto()?->getId() ?? $photos[0]->getId());
                    }

                    $tripIdsWithoutHighlights = array_unique(array_map(fn($trip) => $trip->getId(),
                        array_filter(array_map(fn($date) => $date->getTrip(), $place->getDates()),
                        fn($trip) => $trip !== null && $trip->getMainHighlight() === null)));
                    foreach ($tripIdsWithoutHighlights as &$tripId) {
                        $this->highlightService->createTripHighlight($tripId, $album->getMainPhoto()?->getId() ?? $photos[0]->getId());
                    }
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