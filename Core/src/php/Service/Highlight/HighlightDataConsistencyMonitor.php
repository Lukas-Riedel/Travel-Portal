<?php
    namespace Core\Service\Highlight;

    use Core\Service\Category\CategoryService;
    use Core\Service\Monitoring\DataConsistencyIssue;
    use Core\Service\Monitoring\DataConsistencyMonitor;
use Core\Service\Place\Place;
use Core\Service\Place\PlaceIncludedEntity;
use Core\Service\Place\PlaceService;
use Core\Service\Place\PlaceSortingStrategy;
use Core\Service\Trip\TripIncludedEntity;
use Core\Service\Trip\TripService;
use Core\Service\Trip\TripSortingStrategy;
use Core\Service\Year\YearService;

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
            
            $relevantPlaces = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, null,
                array(PlaceIncludedEntity::Highlights->value), PlaceSortingStrategy::OldestAscending);
            $placesWithHighlightsWithoutQualityAttributes = array_filter($relevantPlaces, fn($place) => $this->hasHighlightsWithNullQuality($place));
            foreach ($placesWithHighlightsWithoutQualityAttributes as &$placeWithHighlightsWithoutQualityAttributes) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::PLACE_HIGHLIGHTS_WITHOUT_QUALITY_ATTRIBUTES_ISSUE_NAME,
                    $placeWithHighlightsWithoutQualityAttributes, time());
            }

            $relevantTrips = $this->tripService->getRegularTrips(null, null, null, array(TripIncludedEntity::Highlights->value), TripSortingStrategy::OldestAscending);
            $tripsWithHighlightsWithoutQualityAttributes = array_filter($relevantTrips, fn($trip) => $this->hasHighlightsWithNullQuality($trip));
            foreach ($tripsWithHighlightsWithoutQualityAttributes as &$tripWithHighlightsWithoutQualityAttributes) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::TRIP_HIGHLIGHTS_WITHOUT_QUALITY_ATTRIBUTES_ISSUE_NAME,
                    $tripWithHighlightsWithoutQualityAttributes, time());
            }

            return $dataConsistencyIssues;
        }

        private function hasHighlightsWithNullQuality(mixed $entity) : bool {
            return count(array_filter(array_map(fn($highlight) => $highlight->getQuality(), $entity->getHighlights()), fn($quality) => $quality === null)) > 0;
        }
    }
?>