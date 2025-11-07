<?php
    namespace Core\Service\Place;

    use Core\Common\CommonConstants;
    use Core\Service\Category\CategoryCategory;
    use Core\Service\Monitoring\DataConsistencyIssue;
    use Core\Service\Monitoring\DataConsistencyMonitor;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;

    class PlaceDataConsistencyMonitor implements DataConsistencyMonitor {

        private const PLACE_WITHOUT_ADMINISTRATIVE_CATEGORY_ISSUE_NAME = "PLACE_WITHOUT_ADMINISTRATIVE_CATEGORY";
        private const DATE_WITHOUT_TIME_ISSUE_NAME = "DATE_WITHOUT_TIME";
        private const DATE_WITH_INCORRECT_TIME_ISSUE_NAME = "DATE_WITH_INCORRECT_TIME";
        private const DATE_WITH_INCORRECT_DURATION_ISSUE_NAME = "DATE_WITH_INCORRECT_DURATION";
        private const DUPLICATED_PLACE_ISSUE_NAME = "DUPLICATED_PLACE";
        private const NON_REVIEWED_PLACE_ISSUE_NAME = "NON_REVIEWED_PLACE";

        private readonly PlaceService $placeService;

        public function __construct(PlaceService $placeService) {
            $this->placeService = $placeService;
        }

        public function fetchDataConsistencyIssues() : array {
            $dataConsistencyIssues = array();

            $relevantPlaces = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null,
                time(), null, array(PlaceIncludedEntity::Categories->value), PlaceSortingStrategy::OldestAscending);

            $placesWithoutAdministrativeCategory = array_filter($relevantPlaces, fn($place) => $place->getName() != $place->getCountry()
                && count(array_filter($place->getCategories(), fn($category) => $category->getCategory() === CategoryCategory::Administrative)) === 0);
            foreach ($placesWithoutAdministrativeCategory as &$placeWithoutAdministrativeCategory) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::PLACE_WITHOUT_ADMINISTRATIVE_CATEGORY_ISSUE_NAME, 
                    $placeWithoutAdministrativeCategory->getPlaceIdentifier(), time());
            }
            
            $relevantPlaces = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null,
                time(), null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

            $placesWithDatesWithoutTime = array_map(fn($place) => $place->withUpdatedDates(array_filter($place->getDates(), 
                fn($date) => $date->getTrip() !== null && ($date->getEnd() - $date->getStart()) % CommonConstants::ONE_DAY_SECONDS === 0)), $relevantPlaces);
            foreach ($placesWithDatesWithoutTime as &$placeWithDatesWithoutTime) {
                foreach ($placeWithDatesWithoutTime->getDates() as &$dateWithoutTime) {
                    $dataConsistencyIssues[] = new DataConsistencyIssue(self::DATE_WITHOUT_TIME_ISSUE_NAME, 
                        $placeWithDatesWithoutTime->withUpdatedDates(array($dateWithoutTime)), time());                    
                }
            }

            $placesWithDatesWithIncorrectTime = array_map(fn($place) => $place->withUpdatedDates(array_filter($place->getDates(), 
                fn($date) => $date->getTrip() !== null && $date->getStart() % 1800 > 0)), $relevantPlaces);
            foreach ($placesWithDatesWithIncorrectTime as &$placeWithDatesWithIncorrectTime) {
                foreach ($placeWithDatesWithIncorrectTime->getDates() as &$dateWithIncorrectTime) {
                    $dataConsistencyIssues[] = new DataConsistencyIssue(self::DATE_WITH_INCORRECT_TIME_ISSUE_NAME, 
                        $placeWithDatesWithIncorrectTime->withUpdatedDates(array($dateWithIncorrectTime)), time());                    
                }
            }

            $placesWithDatesWithIncorrectDuration = array_map(fn($place) => $place->withUpdatedDates(array_filter($place->getDates(), 
                fn($date) => $date->getTrip() !== null && ($date->getEnd() - $date->getStart()) % 1800 > 0)), $relevantPlaces);
            foreach ($placesWithDatesWithIncorrectDuration as &$placeWithDatesWithIncorrectDuration) {
                foreach ($placeWithDatesWithIncorrectDuration->getDates() as &$dateWithIncorrectDuration) {
                    $dataConsistencyIssues[] = new DataConsistencyIssue(self::DATE_WITH_INCORRECT_DURATION_ISSUE_NAME, 
                        $placeWithDatesWithIncorrectDuration->withUpdatedDates(array($dateWithIncorrectDuration)), time());                    
                }
            }
                        
            $relevantPlaces = array_merge($this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null,
                null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending),
                $this->placeService->getCandidatePlaces(null, null, null, array(PlaceIncludedEntity::Dates->value)));

            $duplicatedPlacesGroups = array_filter(array_values(array_reduce($relevantPlaces,
                function($carry, $place) {
                    // Rounding to two decimal places will consider all places in the circle of +/- 1.1 km as duplicates.
                    $key = round($place->getLatitude(), 2) . "," . round($place->getLongitude(), 2);
                    $carry[$key] ??= array();
                    // Do not add the same place twice (the first one is always a regular place, the other one can be a candidate place).
                    if (count(array_filter($carry[$key], fn($existingPlace) => $existingPlace->getName() == $place->getName())) === 0) {
                        $carry[$key][] = $place;                        
                    }
                    return $carry;
                }, array())), fn($group) => count($group) > 1);
            foreach ($duplicatedPlacesGroups as &$duplicatedPlacesGroup) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::DUPLICATED_PLACE_ISSUE_NAME, $duplicatedPlacesGroup, time());                    
            }

            $nonReviewedPlaces = array_filter($relevantPlaces, fn($place) => count(array_filter($place->getDates(),
                fn($date) => $date->getEnd() < time() && $date->getAlbum() && !$date->getAlbum()->isReviewed())) > 0);
            foreach ($nonReviewedPlaces as &$nonReviewedPlace) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::NON_REVIEWED_PLACE_ISSUE_NAME, $nonReviewedPlace, time());                    
            }

            return $dataConsistencyIssues;
        }
    }
?>