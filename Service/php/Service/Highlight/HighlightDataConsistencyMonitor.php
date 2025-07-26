<?php
    namespace Service\Service\Highlight;

    use Service\Service\Category\CategoryService;
    use Service\Service\Monitoring\DataConsistencyIssue;
    use Service\Service\Monitoring\DataConsistencyMonitor;
use Service\Service\Place\Place;
use Service\Service\Place\PlaceIncludedEntity;
use Service\Service\Place\PlaceService;
use Service\Service\Place\PlaceSortingStrategy;
use Service\Service\Trip\TripIncludedEntity;
use Service\Service\Trip\TripService;
use Service\Service\Trip\TripSortingStrategy;
use Service\Service\Year\YearService;

    class HighlightDataConsistencyMonitor implements DataConsistencyMonitor {

        private const PLACE_HIGHLIGHTS_WITHOUT_QUALITY_ATTRIBUTES_ISSUE_NAME = "PLACE_HIGHLIGHTS_WITHOUT_QUALITY_ATTRIBUTES";
        private const TRIP_HIGHLIGHTS_WITHOUT_QUALITY_ATTRIBUTES_ISSUE_NAME = "TRIP_HIGHLIGHTS_WITHOUT_QUALITY_ATTRIBUTES";

        private readonly PlaceService $placeService;
        private readonly TripService $tripService;

        public function __construct(PlaceService $placeService, TripService $tripService) {
            $this->placeService = $placeService;
            $this->tripService = $tripService;
        }

        public function fetchDataConsistencyIssues() : array {
            $dataConsistencyIssues = array();
            
            $relevantPlaces = $this->placeService->getRegularPlaces(NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                array(PlaceIncludedEntity::Highlights->value), PlaceSortingStrategy::Default);
            $placesWithHighlightsWithoutQualityAttributes = array_filter($relevantPlaces, fn($place) => $this->hasHighlightsWithNullQuality($place));
            foreach ($placesWithHighlightsWithoutQualityAttributes as &$placeWithHighlightsWithoutQualityAttributes) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::PLACE_HIGHLIGHTS_WITHOUT_QUALITY_ATTRIBUTES_ISSUE_NAME,
                    $placeWithHighlightsWithoutQualityAttributes, time());
            }

            $relevantTrips = $this->tripService->getRegularTrips(NULL, NULL, NULL, array(TripIncludedEntity::Highlights->value), TripSortingStrategy::Default);
            $tripsWithHighlightsWithoutQualityAttributes = array_filter($relevantTrips, fn($trip) => $this->hasHighlightsWithNullQuality($trip));
            foreach ($tripsWithHighlightsWithoutQualityAttributes as &$tripWithHighlightsWithoutQualityAttributes) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::TRIP_HIGHLIGHTS_WITHOUT_QUALITY_ATTRIBUTES_ISSUE_NAME,
                    $tripWithHighlightsWithoutQualityAttributes, time());
            }

            return $dataConsistencyIssues;
        }

        private function hasHighlightsWithNullQuality(mixed $entity) : bool {
            return count(array_filter(array_map(fn($highlight) => $highlight->getQuality(), $entity->getHighlights()), fn($quality) => $quality === NULL)) > 0;
        }
    }
?>