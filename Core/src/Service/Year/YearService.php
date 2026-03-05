<?php
    namespace Core\Service\Year;
    
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Statistics\StatisticsService;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\GenerativeContent\GenerativeContentClient;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Fitness\FitnessService;
    use Core\Service\Index\IndexService;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Service\Trip\TripIncludedEntity;
    use Core\Service\Trip\TripSortingStrategy;

    class YearService {
        
        private readonly YearMapper $yearMapper;
        private readonly PlaceService $placeService;
        private readonly IndexService $indexService;
        private readonly HighlightService $highlightService;
        private readonly ConfigurationService $configurationService;
        private readonly GenerativeContentClient $cachingGenerativeContentClient;

        public function __construct(DatabaseClient $databaseClient, FitnessService $fitnessService, PlaceService $placeService, ConfigurationService $configurationService,
            HighlightService $highlightService, StatisticsService $statisticsService, IndexService $indexService, GenerativeContentClient $cachingGenerativeContentClient) {
            $this->yearMapper = new YearMapper($databaseClient, $fitnessService, $highlightService, $statisticsService);
            $this->placeService = $placeService;
            $this->configurationService = $configurationService;
            $this->indexService = $indexService;
            $this->highlightService = $highlightService;
            $this->cachingGenerativeContentClient = $cachingGenerativeContentClient;
        }

        public function refreshYearHighlights(int $yearId, int $count) : void {
            // TODO: Introduce a property for TripService $tripService.
            global $tripService;

            $year = $this->getYear($yearId);
            if ($year === null) {
                return;
            }

            $places = $this->placeService->getRegularPlaces(null, null, null, $yearId, null, null, null, null,
                time(), null, null, array(), PlaceSortingStrategy::ScoreDescending);
            $trips = $tripService->getRegularTrips($yearId, null, time(), array(TripIncludedEntity::Highlights->value), TripSortingStrategy::OldestAscending);

            $prompt = $this->configurationService->getConfigurationEntry("generativeContentPrompts")["yearHighlightsSelecting"];
            $query = $this->cachingGenerativeContentClient->getResponse($prompt, array("places" => implode(", ", array_map(fn($place) => $place->getName(), $places))));

            $selectedPhotoIds = $this->indexService->getSelectedPhotoIdsForYear(array_map(fn($trip) => $trip->getId(), $trips), $query, $count,
                $year->getMainHighlight()?->getPhoto()?->getId(), array_filter(array_map(fn($trip) => $trip->getMainHighlight()?->getPhoto()?->getId(), $trips)));

            foreach ($year->getHighlights() as &$highlight) {
                if (!in_array($highlight->getPhoto()->getId(), $selectedPhotoIds)) {
                    $this->highlightService->removeYearHighlight($yearId, $highlight->getId());
                }
            }

            $existingHighlightPhotoIds = array_map(fn($highlight) => $highlight->getPhoto()->getId(), $year->getHighlights());
            foreach ($selectedPhotoIds as &$photoId) {
                if (!in_array($photoId, $existingHighlightPhotoIds)) {
                    $this->highlightService->createYearHighlight($yearId, $photoId);
                }
            }
        }

        public function getYear(int $year) : ?Year {
            $years = $this->yearMapper->selectYears($year, YearIncludedEntity::values());
            return count($years) === 1 ? $years[0] : null;
        }

        public function getYears(array $includedEntities) : array {
            return $this->yearMapper->selectYears(null, $includedEntities);
        }

        public function getYearIdentifier(int $year) : ?YearIdentifier {
            return $this->yearMapper->selectYearIdentifier($year);
        }

        public function getOrCreateYearIdentifier(int $year) : YearIdentifier {
            $yearIdentifier = $this->getYearIdentifier($year);
            if ($yearIdentifier !== null) {
                return $yearIdentifier;
            }

            $yearIdentifier = new YearIdentifier($year, null);
            $this->yearMapper->insertYearIdentifier($yearIdentifier);

            return $yearIdentifier;
        }

        public function updateYearMainHighlight(int $year, ?string $highlightIdentifier) : bool {
            return $this->yearMapper->updateYearMainHighlight($year, $highlightIdentifier);
        }
    }
?>