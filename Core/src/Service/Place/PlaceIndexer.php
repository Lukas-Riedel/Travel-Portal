<?php
    namespace Core\Service\Place;

    use Core\Common\CommonConstants;
    use Core\Service\Geocoding\GeocodingService;
    use Core\Service\Index\DocumentBuffer;
    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;
    use Core\Service\Index\IndexType;

    class PlaceIndexer implements EntityIndexer {
        
        private const YEAR_FORMAT = "Y";

        private const NEARBY_PLACES_COUNT = 5;
        private const NEARBY_PLACE_THRESHOLD_KILOMETERS = 200;

        private readonly PlaceService $placeService;

        private readonly GeocodingService $geocodingService;

        public function __construct(PlaceService $placeService, GeocodingService $geocodingService) {
            $this->placeService = $placeService;
            $this->geocodingService = $geocodingService;
        }

        public function index(DocumentBuffer $documentBuffer, IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : void {
            if ($indexType === IndexType::Composite && $entityType === IndexableEntityType::Place) {
                $places = $entityId !== null
                    ? array($this->placeService->getRegularPlace($entityId, self::NEARBY_PLACES_COUNT))
                    : $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, time(), self::NEARBY_PLACES_COUNT, null,
                        array(PlaceIncludedEntity::Categories->value, PlaceIncludedEntity::Dates->value, PlaceIncludedEntity::Labels->value), PlaceSortingStrategy::OldestAscending);

                foreach ($places as &$place) {
                    $terms = array($place->getName());

                    foreach ($place->getCategories() as &$category) {
                        $terms[] = $category->getName();
                    }

                    foreach ($place->getLabels() as &$label) {
                        $terms[] = $label->getName();
                    }

                    foreach ($place->getDates() as &$date) {
                        $terms[] = date(self::YEAR_FORMAT, $date->getStart());
                        $terms[] = date(CommonConstants::DMY_DATE_FORMAT, $date->getStart());

                        $trip = $date->getTrip();
                        if ($trip !== null) {
                            $terms[] = $trip->getName();
                        }
                    }

                    foreach ($place->getNearbyPlaces() as &$nearbyPlace) {
                        if ($this->geocodingService->getDistance($place->getLatitude(), $place->getLongitude(), $nearbyPlace->getLatitude(), $nearbyPlace->getLongitude()) <= self::NEARBY_PLACE_THRESHOLD_KILOMETERS) {
                            $terms[] = $nearbyPlace->getName();                            
                        }
                    }

                    $documentBuffer->add($place->getId(), $terms);
                }
            }
        }
    }
?>