<?php
    namespace Core\Service\Photo;

    use Core\Service\Index\DocumentBuffer;
    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;
    use Core\Service\Index\IndexType;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Service\Trip\TripIncludedEntity;
    use Core\Service\Trip\TripService;
    use Core\Service\Trip\TripSortingStrategy;

    class PhotoIndexer implements EntityIndexer {

        private readonly PhotoService $photoService;
        private readonly PlaceService $placeService;
        private readonly TripService $tripService;

        public function __construct(PhotoService $photoService, PlaceService $placeService, TripService $tripService) {
            $this->photoService = $photoService;
            $this->placeService = $placeService;
            $this->tripService = $tripService;
        }

        public function index(DocumentBuffer $documentBuffer, IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void {            
            if ($indexType === IndexType::Photo && $entityType === IndexableEntityType::Photo) {
                $places = $this->placeService->getRegularPlaces(null, null, null, null, $entityId, null, null,
                    null, null, null, null, array(PlaceIncludedEntity::Dates->value, PlaceIncludedEntity::Highlights->value),
                    PlaceSortingStrategy::OldestAscending);
                $trips = $this->tripService->getRegularTrips(null, null, null, array(TripIncludedEntity::Highlights->value), 
                    TripSortingStrategy::OldestAscending);

                $tripHighlightIds = array();
                foreach ($trips as &$trip) {
                    $tripHighlightIds[$trip->getId()] = array();
                    foreach ($trip->getHighlights() as &$highlight) {
                        $tripHighlightIds[$trip->getId()][$highlight->getPhoto()->getId()] = $highlight->getId();
                    }
                }

                foreach ($places as &$place) {
                    $placeHighlightIds = array();
                    foreach ($place->getHighlights() as &$highlight) {
                        $placeHighlightIds[$highlight->getPhoto()->getId()] = $highlight->getId();
                    }

                    foreach ($place->getDates() as &$date) {
                        $album = $date->getAlbum();

                        if ($album !== null) {
                            $photoEmbeddings = $this->photoService->getPhotoEmbeddings($album->getId());
                            $tripId = $date->getTrip()?->getId();

                            foreach ($photoEmbeddings as &$photoEmbedding) {
                                $documentBuffer->add($photoEmbedding->getId(), array(
                                    "embedding" => $photoEmbedding->getEmbedding(),
                                    "placeId" => $place->getId(),
                                    "tripId" => $tripId,
                                    "albumId" => $album->getId(),
                                    "highlightId" => isset($placeHighlightIds[$photoEmbedding->getId()]) ? $placeHighlightIds[$photoEmbedding->getId()]
                                        : ($tripId !== null && isset($tripHighlightIds[$tripId][$photoEmbedding->getId()]) ? $tripHighlightIds[$tripId][$photoEmbedding->getId()] 
                                        : null),
                                    "iso" => $photoEmbedding->getIso(),
                                    "isPlaceHighlight" => isset($placeHighlightIds[$photoEmbedding->getId()]),
                                    "isTripHighlight" => $tripId !== null && isset($tripHighlightIds[$tripId][$photoEmbedding->getId()]),
                                    "isPlaceMainHighlight" => $place->getMainHighlight()?->getPhoto()?->getId() === $photoEmbedding->getId()
                                ));
                            }
                        }
                    }
                }
            }
        }
    }
?>