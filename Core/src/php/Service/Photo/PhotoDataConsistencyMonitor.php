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

        private readonly PhotoService $photoService;

        private readonly PlaceService $placeService;

        public function __construct(PhotoService $photoService, PlaceService $placeService) {
            $this->photoService = $photoService;
            $this->placeService = $placeService;
        }

        public function fetchDataConsistencyIssues() : array {
            $dataConsistencyIssues = array();

            $relevantPlaces = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null,
                null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
            $allAlbums = $this->photoService->getAllAlbums();

            $allAlbumIds = array_map(fn($album) => $album->getId(), $allAlbums);
            $referencedAlbumIds = array_map(fn($date) => $date->getAlbum()->getId(), array_filter(array_merge(...array_map(
                fn($place) => $place->getDates(), $relevantPlaces)), fn($date) => $date->getAlbum() !== null));
            $nonReferencedAlbumIds = array_diff($allAlbumIds, $referencedAlbumIds);
            foreach ($nonReferencedAlbumIds as &$nonReferencedAlbumId) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::ALBUM_WITHOUT_PLACE_ISSUE_NAME, 
                    $this->photoService->getAlbum($nonReferencedAlbumId), time());
            }

            $emptyAlbums = array_filter($allAlbums, fn($album) => $album->getImagesCount() === 0);
            foreach ($emptyAlbums as &$emptyAlbum) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::EMPTY_ALBUM_ISSUE_NAME, $emptyAlbum, time());
            }

            return $dataConsistencyIssues;
        }
    }
?>