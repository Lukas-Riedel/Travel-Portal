<?php
    namespace Core\Service\Photo;

    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;
    use Core\Service\Index\IndexType;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;

    class PhotoIndexer implements EntityIndexer {

        private readonly PhotoService $photoService;

        private readonly PlaceService $placeService;

        public function __construct(PhotoService $photoService, PlaceService $placeService) {
            $this->photoService = $photoService;
            $this->placeService = $placeService;
        }

        public function index(IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : array {
            $result = array();
            
            if ($indexType === IndexType::Photo && $entityType === IndexableEntityType::Album) {
                $places = $this->placeService->getRegularPlaces(null, null, null, null, $entityId, null, null,
                    null, time(), null, null, array(PlaceIncludedEntity::Dates->value, PlaceIncludedEntity::Highlights->value),
                    PlaceSortingStrategy::OldestAscending);

                foreach ($places as &$place) {
                    $placeHighlightPhotoIds = array_flip(array_map(fn($highlight) => $highlight->getPhoto()->getId(), $place->getHighlights()));

                    foreach ($place->getDates() as &$date) {
                        $album = $date->getAlbum();

                        if ($album !== null) {
                            $photoEmbeddings = $this->photoService->getPhotoEmbeddings($album->getId());

                            foreach ($photoEmbeddings as &$photoEmbedding) {
                                $result[$photoEmbedding->getId()] = array(
                                    "embedding" => $photoEmbedding->getEmbedding(),
                                    "placeId" => $place->getId(),
                                    "tripId" => $date->getTrip()?->getId() ?? null,
                                    "albumId" => $album->getId(),
                                    "isPlaceHighlight" => isset($placeHighlightPhotoIds[$photoEmbedding->getId()]),
                                    "isPlaceMainHighlight" => $place->getMainHighlight()?->getPhoto()?->getId() === $photoEmbedding->getId()
                                );
                            }
                        }
                    }
                }
            }

            return $result;
        }
    }
?>