<?php
    namespace Service\Service\Photo;

    use Service\Service\Monitoring\DataConsistencyIssue;
    use Service\Service\Monitoring\DataConsistencyMonitor;
    use Service\Service\Place\PlaceIncludedEntity;
    use Service\Service\Place\PlaceService;
    use Service\Service\Place\PlaceSortingStrategy;

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

            $relevantPlaces = $this->placeService->getRegularPlaces(NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                NULL, NULL, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::Default);
            $allAlbums = $this->photoService->getAllAlbums();

            $allAlbumIds = array_map(fn($album) => $album->getId(), $allAlbums);
            $referencedAlbumIds = array_map(fn($date) => $date->getAlbum()->getId(), array_filter(array_merge(...array_map(
                fn($place) => $place->getDates(), $relevantPlaces)), fn($date) => $date->getAlbum() !== NULL));
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