<?php
    namespace Service\Service\Category;

    use Service\Service\Configuration\ConfigurationService;
    use Service\Service\Highlight\HighlightService;
    use Service\Service\Place\PlaceIdentifier;
    use Service\Service\Statistics\StatisticsService;

    class CategoryService {

        private const CIRCLE_APPROXIMATION_POINTS_COUNT = 10;

        private readonly CategoryMapper $categoryMapper;

        private readonly ConfigurationService $configurationService;
        
        private readonly \EventPublisher $eventPublisher;

        private array $categoryIdToCategoryIdentifierCache = array();
        private array $placeIdToCategoryIdsCache = array();

        public function __construct(\DatabaseProvider $databaseProvider, ConfigurationService $configurationService,
            HighlightService $highlightService, StatisticsService $statisticsService, \EventPublisher $eventPublisher) {
            $this->categoryMapper = new CategoryMapper($databaseProvider, $highlightService, $statisticsService);
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
        }

        public function updateCategories(PlaceIdentifier $placeIdentifier) : void {
            $categoryIds = array();
            
            // Include country category.
            $countryCategoryIdentifier = $this->getOrCreateCountryCategoryIdentifier($placeIdentifier->getCountry());
            $categoryIds[] = $countryCategoryIdentifier->getId();
        
            // Include geographical region categories.
            $point = $this->getWktPoint($placeIdentifier->getLatitude(), $placeIdentifier->getLongitude());
            foreach ($this->categoryMapper->selectAllGeographicalRegions() as &$geographicalRegion) {
                if ($geographicalRegion->getCountryCategoryId() === NULL
                    // TODO: '==' must be here because '===' doesn't work, find out why.
                    || $geographicalRegion->getCountryCategoryId() == $countryCategoryIdentifier->getId()) {
                    if ($this->isPointInPolygon($geographicalRegion->getGeoJson(), $point)) {
                        $categoryIds[] = $geographicalRegion->getCategoryId();
                    }
                    else if ($geographicalRegion->getRadius() > 0) {
                        foreach ($this->getWktPointsOnCircle($placeIdentifier->getLatitude(), $placeIdentifier->getLongitude(),
                            $geographicalRegion->getRadius(), self::CIRCLE_APPROXIMATION_POINTS_COUNT) as &$pointOnCircle) {
                            if ($this->isPointInPolygon($geographicalRegion->getGeoJson(), $pointOnCircle)) {
                                $categoryIds[] = $geographicalRegion->getCategoryId();
                                break;
                            }
                        }
                    }
                }
            }

            // Include composite region categories.
            foreach ($this->categoryMapper->selectAllCompositeRegions() as &$compositeRegion) {
                if ($this->arrayAny($compositeRegion->getIncludedCategoryIds(), function($includedCategoryId)
                        use(&$categoryIds) { return in_array($includedCategoryId, $categoryIds); })
                    && $this->arrayEvery($compositeRegion->getExcludedCategoryIds(), function($excludedCategoryId)
                        use(&$categoryIds) { return !in_array($excludedCategoryId, $categoryIds); })) {
                    $categoryIds[] = $compositeRegion->getCategoryId();
                }
            }

            // Persist the categories.
            $this->categoryMapper->deleteCategories($placeIdentifier->getId());

            foreach (array_unique($categoryIds) as &$categoryId) {  
                $this->categoryMapper->insertCategory($placeIdentifier->getId(), $categoryId);
                $this->eventPublisher->publishCategoryUpdatedEvent($categoryId);
            }
        }

        public function getCategoryIdentifier(string $name) : ?CategoryIdentifier { 
            return $this->categoryMapper->selectCategoryIdentifier($name);
        }

        public function getCategoryIdentifierById(string $categoryId) : ?CategoryIdentifier {
            if (!isset($this->categoryIdToCategoryIdentifierCache[$categoryId])) {
                $this->categoryIdToCategoryIdentifierCache[$categoryId] = $this->categoryMapper->selectCategoryIdentifierById($categoryId);
            }
            return $this->categoryIdToCategoryIdentifierCache[$categoryId];
        }

        public function getCategoryIdsForPlace(string $placeId) : array { 
            return $this->categoryMapper->selectCategoryIdsForPlace($placeId);
        }

        public function getPlaceIdsForCategoryId(string $categoryId) : array {
            return $this->categoryMapper->selectPlaceIdsForCategory($categoryId);
        }

        public function getCategoryIdsForCategory(?CategoryCategory $category) : array {
            return $this->categoryMapper->selectCategoryIdsForCategory($category);
        }

        public function getCategoryIdentifiersForPlace(string $placeId) : array {
            if (empty($this->placeIdToCategoryIdsCache)) {
                $this->placeIdToCategoryIdsCache = $this->categoryMapper->selectCategoryIdsForAllPlaceIds();
            }

            return $this->getCategoryIdentifiersByIds($this->placeIdToCategoryIdsCache[$placeId] ?? array());
        }

        public function getCategoryIdentifiersByIds(array $categoryIds) : array { 
            $categories = array();

            foreach ($categoryIds as &$categoryId) {
                $category = $this->getCategoryIdentifierById($categoryId);
                if ($category !== NULL) {
                    $categories[] = $category;
                }
            }
            
            return $categories;
        }

        public function getCategory(string $categoryId) : ?Category {
            $categories = $this->categoryMapper->selectCategories($categoryId, NULL, CategoryCategory::values(), CategoryIncludedEntity::values());
            return count($categories) === 1 ? $categories[0] : NULL;
        }

        public function getCategories(?string $country, array $categoryCategories, array $includedEntities) : array {
            $countryCategoryId = $country === NULL ? NULL : $this->getOrCreateCountryCategoryIdentifier($country)->getId();
            return $this->categoryMapper->selectCategories(NULL, $countryCategoryId, $categoryCategories, $includedEntities);
        }

        public function getAllGeographicalRegions() : array {
            return $this->categoryMapper->selectAllGeographicalRegions();
        }

        public function updateCategoryMainHighlight(string $categoryId, string $highlightIdentifier) : bool {
            return $this->categoryMapper->updateCategoryMainHighlight($categoryId, $highlightIdentifier);
        }

        public function updateCategoryName(string $categoryId, string $name) : bool {            
            $wasUpdated = $this->categoryMapper->updateCategoryName($categoryId, $name);
            
            if ($wasUpdated) {
                $this->eventPublisher->publishCategoryUpdatedEvent($categoryId);
            }

            return $wasUpdated;
        }

        public function updateCategoryColor(string $categoryId, string $color) : bool {            
            return $this->categoryMapper->updateCategoryColor($categoryId, $color);
        }

        public function updateCategoryUnicode(string $categoryId, string $unicode) : bool {            
            return $this->categoryMapper->updateCategoryUnicode($categoryId, $unicode);
        }

        public function updateCategoryPublicHolidaysCalendar(string $categoryId, string $publicHolidaysCalendar) : bool {            
            return $this->categoryMapper->updateCategoryPublicHolidaysCalendar($categoryId, $publicHolidaysCalendar);
        }

        // TODO: Replace string $category by CategoryCategory $category.
        public function createCompositeRegion(string $name, string $category, array $includedRegions, array $excludedRegions) : CategoryIdentifier {
            foreach ($this->configurationService->getConfigurationEntry("countryNames") as $country) {
                $this->getOrCreateCountryCategoryIdentifier($country["name"]);
            }

            // Verify that all referenced regions exist.
            $referencableRegionNames = $this->categoryMapper->selectAllCategoryNames();

            foreach ($includedRegions as &$includedRegion) {
                if (!in_array($includedRegion, $referencableRegionNames)) {
                    throw new \InvalidArgumentException("The included region '" . $includedRegion . "' does not exist.");
                }
            }

            foreach ($excludedRegions as &$excludedRegion) {
                if (!in_array($excludedRegion, $referencableRegionNames)) {
                    throw new \InvalidArgumentException("The excluded region '" . $excludedRegion . "' does not exist.");
                }
            }

            // Create the region.
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category);
            $this->categoryMapper->deleteCompositeRegion($categoryIdentifier->getId());

            foreach ($includedRegions as &$includedRegion) {
                $subjectCategoryIdentifier = $this->getCategoryIdentifier($includedRegion);
                $this->categoryMapper->insertCompositeRegionInclusion($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId());
                $this->eventPublisher->publishCategoryInvalidatedEvent($subjectCategoryIdentifier->getId());
            }

            foreach ($excludedRegions as &$excludedRegion) {
                $subjectCategoryIdentifier = $this->getCategoryIdentifier($excludedRegion);
                $this->categoryMapper->insertCompositeRegionExclusion($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId());
            }
    
            $this->eventPublisher->publishCategoryCreatedEvent($categoryIdentifier->getId());
            
            return $categoryIdentifier;
        }

        // TODO: Replace string $category by CategoryCategory $category.
        public function createGeographicalRegion(string $name, ?string $country, string $category, int $radius, mixed $geoJson) : CategoryIdentifier {  
            $countryCategoryId = $country === NULL ? NULL : $this->getOrCreateCountryCategoryIdentifier($country)->getId();                                  
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category); 
            $this->categoryMapper->deleteGeographicalRegion($categoryIdentifier->getId(), $countryCategoryId);
            $this->categoryMapper->insertGeographicalRegion(new GeographicalRegion($categoryIdentifier->getId(), $countryCategoryId, $radius, $geoJson));

            if ($countryCategoryId === NULL) {
                foreach ($this->getCategories(NULL, array(CategoryCategory::Country->value), array()) as &$category) {
                    $this->eventPublisher->publishCategoryInvalidatedEvent($category->getId());
                }
            }
            else {
                $this->eventPublisher->publishCategoryInvalidatedEvent($countryCategoryId);
            }
    
            $this->eventPublisher->publishCategoryCreatedEvent($categoryIdentifier->getId());
            
            return $categoryIdentifier;
        }

        // TODO: Replace string $category by CategoryCategory $category.
        public function createGeographicalRegionExtensionRegion(string $name, ?string $country, string $category, float $latitude, float $longitude) : CategoryIdentifier {
            $geoJson = json_encode(array(
                "type" => "Feature", 
                "geometry" => array(
                    "type" => "Point", 
                    "coordinates" => array(
                        floatval($longitude), 
                        floatval($latitude)))), TRUE);
            
            $countryCategoryId = $country === NULL ? NULL : $this->getOrCreateCountryCategoryIdentifier($country)->getId();   
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category);
            $this->categoryMapper->insertGeographicalRegion(new GeographicalRegion($categoryIdentifier->getId(), $countryCategoryId, 0, $geoJson));

            // TODO: Improve by publishing an event that would invalidate categories only for the specific coordinates.
            if ($country === NULL) {
                foreach ($this->getCategories(NULL, array(CategoryCategory::Country->value), array()) as &$category) {
                    $this->eventPublisher->publishCategoryInvalidatedEvent($category->getId());
                }
            }
            else {
                $this->eventPublisher->publishCategoryInvalidatedEvent($this->getCategoryIdentifier($country)->getId());
            }

            return $categoryIdentifier;
        }

        public function updateRegionAreas() : void {            
            $regionAreas = array();

            // Include geographical region.
            foreach ($this->categoryMapper->selectAllNonTrivialGeographicalRegions() as &$geographicalRegion) {
                $area = $geographicalRegion->getGeoJson()->getArea();
                $regionAreas[$geographicalRegion->getCategoryId()] = $area;

                // Include country regions.
                if ($geographicalRegion->getCountryCategoryId() !== NULL) {
                    if ($geographicalRegion->getCountryCategoryId() !== NULL) {
                        if (!array_key_exists($geographicalRegion->getCountryCategoryId(), $regionAreas)) {
                            $regionAreas[$geographicalRegion->getCountryCategoryId()] = 0;
                        }
                        $regionAreas[$geographicalRegion->getCountryCategoryId()] += $area;
                    }
                }
            }

            // Include composite regions.
            foreach ($this->categoryMapper->selectAllCompositeRegions() as &$compositeRegion) {
                $compositeRegionArea = 0;

                foreach ($compositeRegion->getIncludedCategoryIds() as &$includedCategoryId) {
                    if (array_key_exists($includedCategoryId, $regionAreas)) {
                        $compositeRegionArea += $regionAreas[$includedCategoryId];
                    }
                }

                foreach ($compositeRegion->getExcludedCategoryIds() as &$excludedCategoryId) {
                    if (array_key_exists($excludedCategoryId, $regionAreas)) {
                        $compositeRegionArea -= $regionAreas[$excludedCategoryId];
                    }
                }

                $regionAreas[$compositeRegion->getCategoryId()] = $compositeRegionArea;
            }

            // Persist the region areas.
            $this->categoryMapper->deleteAllRegionAreas();

            foreach ($regionAreas as $categoryId => $area) {
                $this->categoryMapper->insertRegionArea($categoryId, $area);
            }
        }

        public function getAllNonTrivialGeographicalRegions() : array {
            return $this->categoryMapper->selectAllNonTrivialGeographicalRegions();
        }

        public function getOrCreateCountryCategoryIdentifier(string $country) : CategoryIdentifier {
            if (!in_array($country, array_map(fn($country) => $country["name"], $this->configurationService->getConfigurationEntry("countryNames")))) {
                throw new \InvalidArgumentException("The country '" . $country . "' does not exist.");
            }

            return $this->getOrCreateCategoryIdentifier($country, CategoryCategory::Country->value);
        }

        private function getWktPointsOnCircle(float $latitude, float $longitude, int $radiusInKms, int $pointsCount) : array {    
            $points = array();
    
            for ($i = 0; $i < $pointsCount; $i++) {
                $points[] = $this->getWktPoint(
                    $latitude + $this->positionLatitude($pointsCount, $i, $radiusInKms / 111), 
                    $longitude + $this->positionLongitude($pointsCount, $i, $radiusInKms / 111)
                );
            }
    
            return $points;
        }
        
        // TODO: Replace string $category by CategoryCategory $category.
        private function getOrCreateCategoryIdentifier(string $name, string $category) : CategoryIdentifier {
            $categoryIdentifier = $this->getCategoryIdentifier($name);
            if ($categoryIdentifier !== NULL) {
                return $categoryIdentifier;
            }

            $categoryIdentifier = new CategoryIdentifier(NULL, $name, CategoryCategory::from($category), NULL, NULL);
            $this->categoryMapper->insertCategoryIdentifier($categoryIdentifier);

            return $categoryIdentifier;
        }
    
        private function positionLatitude(int $count, int $index, float $radius) : float {
            $alpha = 360 / $count;
            $angle = $alpha * $index;
            return $radius * cos(deg2rad($angle));
        }
          
        private function positionLongitude(int $count, int $index, float $radius) : float {
            $alpha = 360 / $count;
            $angle = $alpha * $index;
            return $radius * sin(deg2rad($angle));
        }

        private function getWktPoint(float $latitude, float $longitude) : mixed {
            return \geoPHP::load("POINT (" . $longitude . " " . $latitude . ")", "wkt");
        }

        private function isPointInPolygon(mixed $geoJson, mixed $point) : bool {
            if (method_exists($geoJson, "pointInPolygon")) {
                return $geoJson->pointInPolygon($point);
            }

            if (method_exists($geoJson, "getComponents")) {
                $pointInPolygon = FALSE;
                foreach ($geoJson->getComponents() as &$component) {
                    if ($component->pointInPolygon($point, $pointInPolygon)) {
                        $pointInPolygon = TRUE;
                    }
                }
                return $pointInPolygon;
            }

            return $geoJson->equals($point);
        }

        private function arrayAny(array $array, mixed $fn) : bool {
            foreach ($array as &$value) {
                if ($fn($value)) {
                    return TRUE;
                }
            }
            return FALSE;
        }
    
        private function arrayEvery(array $array, mixed $fn) : bool {
            foreach ($array as &$value) {
                if (!$fn($value)) {
                    return FALSE;
                }
            }
            return TRUE;
        }
    }
?>