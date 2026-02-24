<?php
    namespace Core\Service\Place;

    use Core\Common\CommonConstants;
    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;

    class PlaceIndexer implements EntityIndexer {
        
        private const YEAR_FORMAT = "Y";

        private readonly PlaceService $placeService;

        public function __construct(PlaceService $placeService) {
            $this->placeService = $placeService;
        }

        public function index(IndexableEntityType $entityType) : array {
            $result = array();

            if ($entityType === IndexableEntityType::Place) {
                $places = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, time(), null, null,
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

                    $result[$place->getId()] = $terms;
                }
            }

            return $result;
        }
    }
?>