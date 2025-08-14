<?php
    namespace Core\Service\Category;

    use Core\Service\Monitoring\DataConsistencyIssue;
    use Core\Service\Monitoring\DataConsistencyMonitor;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;

    class CategoryDataConsistencyMonitor implements DataConsistencyMonitor {

        private const COUNTRY_WITHOUT_ADMINISTRATIVE_DIVISION_ISSUE_NAME = "COUNTRY_WITHOUT_ADMINISTRATIVE_DIVISION";
        private const GEOGRAPHICAL_REGIONS_WITH_SAME_NAME_ISSUE_NAME = "GEOGRAPHICAL_REGIONS_WITH_SAME_NAME";
        private const COUNTRY_WITH_INCOMPLETE_METADATA_ISSUE_NAME = "COUNTRY_WITH_INCOMPLETE_METADATA";

        private readonly CategoryService $categoryService;

        private readonly PlaceService $placeService;

        public function __construct(CategoryService $categoryService, PlaceService $placeService) {
            $this->categoryService = $categoryService;
            $this->placeService = $placeService;
        }

        public function fetchDataConsistencyIssues() : array {
            $dataConsistencyIssues = array();

            $relevantPlaces = $this->placeService->getRegularPlaces(NULL, NULL, NULL, NULL, NULL, NULL, NULL,
                NULL, time(), array(PlaceIncludedEntity::Categories->value), PlaceSortingStrategy::Default);
            $countryCategories = $this->categoryService->getCategories(NULL, array(CategoryCategory::Country->value), array());
            $geographicalRegions = $this->categoryService->getAllGeographicalRegions();

            $visitedCountryNames = array_keys(array_filter(array_count_values(array_map(fn($place) => $place->getCountry(),
                $relevantPlaces)), fn($count) => $count > 1));
            $visitedCountryCategoryIds = array_map(fn($category) => $category->getId(), array_filter($countryCategories,
                fn($category) => in_array($category->getName(), $visitedCountryNames)));
            $visitedCountryCategoryIdsWithoutGeographicalRegions = array_filter($visitedCountryCategoryIds,
                fn($countryCategoryId) => count(array_filter($geographicalRegions, 
                    fn($geographicalRegion) => $geographicalRegion->getCountryCategoryId() == $countryCategoryId)) === 0);
            foreach ($visitedCountryCategoryIdsWithoutGeographicalRegions as &$visitedCountryCategoryIdWithoutGeographicalRegions) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::COUNTRY_WITHOUT_ADMINISTRATIVE_DIVISION_ISSUE_NAME, 
                    $this->categoryService->getCategoryIdentifierById($visitedCountryCategoryIdWithoutGeographicalRegions), time());
            }

            $allNonTrivialGeographicalRegions = $this->categoryService->getAllNonTrivialGeographicalRegions();
            $duplicatedCategoryIds = array_keys(array_filter(array_count_values(array_map(fn($region) => $region->getCategoryId(),
                $allNonTrivialGeographicalRegions)), fn($count) => $count > 1));
            foreach ($duplicatedCategoryIds as &$duplicatedCategoryId) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::GEOGRAPHICAL_REGIONS_WITH_SAME_NAME_ISSUE_NAME,
                    $this->categoryService->getCategoryIdentifierById($duplicatedCategoryId), time());
            }
            
            $allPlaceIdentifiers = $this->placeService->getAllPlaceIdentifiers();
            $plannedCountryNames = array_unique(array_map(fn($placeIdentifier) => $placeIdentifier->getCountry(), $allPlaceIdentifiers));
            $plannedCountryCategories = array_filter($countryCategories,
                fn($category) => in_array($category->getName(), $plannedCountryNames));

            $countryCategoriesWithIncompleteMetadata = array_filter($plannedCountryCategories, 
                fn($category) => $category->getMetadata() === NULL || !$category->getMetadata()->isComplete());
            foreach ($countryCategoriesWithIncompleteMetadata as &$countryCategoryWithIncompleteMetadata) {
                $dataConsistencyIssues[] = new DataConsistencyIssue(self::COUNTRY_WITH_INCOMPLETE_METADATA_ISSUE_NAME,
                    $countryCategoryWithIncompleteMetadata, time());
            }

            return $dataConsistencyIssues;
        }
    }
?>