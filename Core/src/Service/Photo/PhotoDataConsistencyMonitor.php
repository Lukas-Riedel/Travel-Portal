<?php
    namespace Core\Service\Photo;

    use Core\Service\Monitoring\DataConsistencyIssue;
    use Core\Service\Monitoring\DataConsistencyMonitor;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;

    class PhotoDataConsistencyMonitor implements DataConsistencyMonitor {

        private const ALBUM_WITHOUT_PLACE_ISSUE_NAME = "ALBUM_WITHOUT_PLACE";
        private const EMPTY_ALBUM_ISSUE_NAME = "EMPTY_ALBUM";
        private const REPLACED_PHOTO_ISSUE_NAME = "REPLACED_PHOTO";

        private readonly PhotoService $photoService;
        private readonly PlaceService $placeService;

        public function __construct(PhotoService $photoService, PlaceService $placeService) {
            $this->photoService = $photoService;
            $this->placeService = $placeService;
        }

        public function fetchDataConsistencyIssues() : array {
            $dataConsistencyIssues = array();

            $relevantPlaces = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, null,
                null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
            $allAlbums = $this->photoService->getAllAlbums();

            $referencedAlbumIds = array();
            foreach ($relevantPlaces as &$place) {
                foreach ($place->getDates() as &$date) {
                    if ($date->getAlbum() !== null) {
                        $referencedAlbumIds[$date->getAlbum()->getId()] = true;
                    }
                }
            }

            foreach ($allAlbums as &$album) {
                $albumId = $album->getId();
                if (!isset($referencedAlbumIds[$albumId])) {
                    $dataConsistencyIssues[] = new DataConsistencyIssue(self::ALBUM_WITHOUT_PLACE_ISSUE_NAME, $albumId, $album, time());
                }
            }

            $emptyAlbums = array_filter($allAlbums, fn($album) => $album->getImagesCount() === 0);
            foreach ($emptyAlbums as &$emptyAlbum) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::EMPTY_ALBUM_ISSUE_NAME, $emptyAlbum->getId(), $emptyAlbum, time());
            }

            $replacedPhotos = $this->photoService->getReplacedPhotos();
            foreach ($replacedPhotos as &$replacedPhoto) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::REPLACED_PHOTO_ISSUE_NAME, $replacedPhoto->getId(), $replacedPhoto, time());
            }

            return $dataConsistencyIssues;
        }
    }
?>