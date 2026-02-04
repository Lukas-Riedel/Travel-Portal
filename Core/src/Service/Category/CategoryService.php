<?php
    namespace Core\Service\Category;

    use Common\Client\Cache\CacheClient;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Highlight\HighlightService;
    use Core\Service\Place\PlaceIdentifier;
    use Core\Service\Statistics\StatisticsService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;

    class CategoryService {

        private const CATEGORY_IDENTIFIER_CACHE_KEY_FORMAT = "CategoryService:CategoryIdentifier:%s";
        private const CATEGORY_IDENTIFIER_CACHE_TTL = 60;

        private const PLACE_CATEGORIES_CACHE_KEY = "CategoryService:PlaceCategories";
        private const PLACE_CATEGORIES_CACHE_TTL = 60;

        private const CIRCLE_APPROXIMATION_POINTS_COUNT = 10;

        private readonly CategoryMapper $categoryMapper;
        
        private readonly EventPublisher $eventPublisher;

        private readonly CacheClient $memoryCacheClient;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, ConfigurationService $configurationService,
            HighlightService $highlightService, StatisticsService $statisticsService, CacheClient $memoryCacheClient, EventPublisher $eventPublisher) {
            $this->categoryMapper = new CategoryMapper($databaseClient, $highlightService, $statisticsService, $configurationService);
            $this->eventPublisher = $eventPublisher;
            $this->memoryCacheClient = $memoryCacheClient;
            $this->transactionManager = $databaseClient;
        }

        public function updateCategories(PlaceIdentifier $placeIdentifier) : void {
            $categoryIds = array();
            
            // Include country category.
            if ($placeIdentifier->getCountry() !== null) {
                $countryCategoryIdentifier = $this->getOrCreateCountryCategoryIdentifier($placeIdentifier->getCountry());
                $categoryIds[] = $countryCategoryIdentifier->getId();                
            }
        
            // Include geographical region categories.
            $point = $this->getWktPoint($placeIdentifier->getLatitude(), $placeIdentifier->getLongitude());
            foreach ($this->categoryMapper->selectGeographicalRegions(null) as &$geographicalRegion) {
                if ($geographicalRegion->getCountryCategory()?->getId() === null
                    // TODO: '==' must be here because '===' doesn't work, find out why.
                    || $geographicalRegion->getCountryCategory()->getId() == $countryCategoryIdentifier->getId()) {
                    if ($this->isPointInPolygon($geographicalRegion->getGeoJson(), $point)) {
                        $categoryIds[] = $geographicalRegion->getCategory()->getId();
                    }
                    else if ($geographicalRegion->getRadius() > 0) {
                        foreach ($this->getWktPointsOnCircle($placeIdentifier->getLatitude(), $placeIdentifier->getLongitude(),
                            $geographicalRegion->getRadius(), self::CIRCLE_APPROXIMATION_POINTS_COUNT) as &$pointOnCircle) {
                            if ($this->isPointInPolygon($geographicalRegion->getGeoJson(), $pointOnCircle)) {
                                $categoryIds[] = $geographicalRegion->getCategory()->getId();
                                break;
                            }
                        }
                    }
                }
            }

            // Include composite region categories.
            foreach ($this->categoryMapper->selectCompositeRegions(null) as &$compositeRegion) {
                if ($this->arrayAny($compositeRegion->getIncludedCategories(), function($includedCategory)
                        use(&$categoryIds) { return in_array($includedCategory->getId(), $categoryIds); })
                    && $this->arrayEvery($compositeRegion->getExcludedCategories(), function($excludedCategory)
                        use(&$categoryIds) { return !in_array($excludedCategory->getId(), $categoryIds); })) {
                    $categoryIds[] = $compositeRegion->getCategory()->getId();
                }
            }

            // Persist the categories.
            $this->transactionManager->executeAtomically(function() use(&$placeIdentifier, &$categoryIds) {
                $this->categoryMapper->deleteCategories($placeIdentifier->getId());

                foreach (array_unique($categoryIds) as &$categoryId) {  
                    $this->categoryMapper->insertCategory($placeIdentifier->getId(), $categoryId);
                    $this->eventPublisher->publish(Event::CategoryUpdated($categoryId));
                }
            });
        }

        public function getCategoryIdentifier(string $name) : ?CategoryIdentifier { 
            return $this->categoryMapper->selectCategoryIdentifier($name);
        }

        public function getCategoryIdentifierById(string $categoryId) : ?CategoryIdentifier {
            $cacheKey = $this->getCategoryIdentifierCacheKey($categoryId);
            $cachedCategoryIdentifier = $this->memoryCacheClient->get($cacheKey);
            if ($cachedCategoryIdentifier !== null) {
                return $cachedCategoryIdentifier;
            }

            $categoryIdentifier = $this->categoryMapper->selectCategoryIdentifierById($categoryId);
            $this->memoryCacheClient->set($cacheKey, $categoryIdentifier, self::CATEGORY_IDENTIFIER_CACHE_TTL);
            return $categoryIdentifier;
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
            $cachedPlaceCategories = $this->memoryCacheClient->get(self::PLACE_CATEGORIES_CACHE_KEY);
            if ($cachedPlaceCategories !== null) {
                return $this->getCategoryIdentifiersByIds($cachedPlaceCategories[$placeId] ?? array());
            }

            $placeCategories = $this->categoryMapper->selectCategoryIdsForAllPlaceIds();
            $this->memoryCacheClient->set(self::PLACE_CATEGORIES_CACHE_KEY, $placeCategories, self::PLACE_CATEGORIES_CACHE_TTL);

            return $this->getCategoryIdentifiersByIds($placeCategories[$placeId] ?? array());
        }

        public function getCategoryIdentifiersByIds(array $categoryIds) : array {            
            $categoryIdsToFetch = array();
            foreach ($categoryIds as &$categoryId) {
                if ($this->memoryCacheClient->get($this->getCategoryIdentifierCacheKey($categoryId), self::CATEGORY_IDENTIFIER_CACHE_TTL) === null) {
                    $categoryIdsToFetch[] = $categoryId;
                }
            }

            if (!empty($categoryIdsToFetch)) {
                $fetchedCategoryIdentifiers = $this->categoryMapper->selectCategoryIdentifiersByIds($categoryIdsToFetch);
                foreach ($fetchedCategoryIdentifiers as &$fetchedCategoryIdentifier) {
                    $this->memoryCacheClient->set($this->getCategoryIdentifierCacheKey($fetchedCategoryIdentifier->getId()), $fetchedCategoryIdentifier, self::CATEGORY_IDENTIFIER_CACHE_TTL);
                }
            }
            
            $categoryIdentifiers = array();
            foreach ($categoryIds as &$categoryId) {
                $categoryIdentifiers[] = $this->memoryCacheClient->get($this->getCategoryIdentifierCacheKey($categoryId));
            }
            
            return $categoryIdentifiers;
        }

        public function getCategory(string $categoryId) : ?Category {
            $categories = $this->categoryMapper->selectCategories($categoryId, null, CategoryCategory::values(), CategoryIncludedEntity::values());
            return count($categories) === 1 ? $categories[0] : null;
        }

        public function getCategories(?string $country, array $categoryCategories, array $includedEntities) : array {
            $countryCategoryId = $country === null ? null : $this->getOrCreateCountryCategoryIdentifier($country)->getId();
            return $this->categoryMapper->selectCategories(null, $countryCategoryId, $categoryCategories, $includedEntities);
        }

        public function getRegions(?string $name) : array {
            return array_merge($this->categoryMapper->selectGeographicalRegions($name),
                $this->categoryMapper->selectCompositeRegions($name));
        }

        public function getAllGeographicalRegions() : array {
            return $this->categoryMapper->selectGeographicalRegions(null);
        }

        public function updateCategoryMainHighlight(string $categoryId, ?string $highlightIdentifier) : bool {
            return $this->categoryMapper->updateCategoryMainHighlight($categoryId, $highlightIdentifier);
        }

        public function updateCategoryName(string $categoryId, string $name) : bool {
            $wasUpdated = true;
            $this->transactionManager->executeAtomically(function() use(&$wasUpdated, &$categoryId, &$name) {
                $wasUpdated &= $this->categoryMapper->updateCategoryName($categoryId, $name);                
                if ($wasUpdated) {
                    $this->eventPublisher->publish(Event::CategoryRenamed($categoryId));
                    $this->eventPublisher->publish(Event::CategoryUpdated($categoryId));
                }
            });
            return $wasUpdated;
        }
        public function updateCategoryCategory(string $categoryId, CategoryCategory $category) : bool {
            return $this->categoryMapper->updateCategoryCategory($categoryId, $category);
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
        public function createCompositeRegion(string $name, string $category, array $includedCategories, array $excludedCategories, bool $overwrite) : CompositeRegion {
            // Verify that all referenced regions exist.
            $referencableRegionNames = $this->categoryMapper->selectAllCategoryNames();

            foreach ($includedCategories as &$includedCategory) {
                if (!in_array($includedCategory, $referencableRegionNames)) {
                    throw new \InvalidArgumentException("The included category '" . $includedCategory . "' does not exist.");
                }
            }

            foreach ($excludedCategories as &$excludedCategory) {
                if (!in_array($excludedCategory, $referencableRegionNames)) {
                    throw new \InvalidArgumentException("The excluded category '" . $excludedCategory . "' does not exist.");
                }
            }

            // Create the region.
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category);

            $includedCategoryIdentifiers = array();
            $excludedCategoryIdentifiers = array();

            $this->transactionManager->executeAtomically(function() use(&$categoryIdentifier, &$includedCategories, &$excludedCategories,
                &$includedCategoryIdentifiers, &$excludedCategoryIdentifiers, &$overwrite) {
                if ($overwrite) {
                    $this->categoryMapper->deleteCompositeRegion($categoryIdentifier->getId());                        
                }
                
                // TODO: Block the insertion if the composite region already exists.
                foreach ($includedCategories as &$includedCategory) {
                    $subjectCategoryIdentifier = $this->getCategoryIdentifier($includedCategory);
                    $includedCategoryIdentifiers[] = $subjectCategoryIdentifier;
                    $this->categoryMapper->insertCompositeRegionInclusion($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId());
                    $this->eventPublisher->publish(Event::CategoryInvalidated($subjectCategoryIdentifier->getId()));
                }

                foreach ($excludedCategories as &$excludedCategory) {
                    $subjectCategoryIdentifier = $this->getCategoryIdentifier($excludedCategory);
                    $excludedCategoryIdentifiers[] = $subjectCategoryIdentifier;
                    $this->categoryMapper->insertCompositeRegionExclusion($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId());
                }
                
                $this->eventPublisher->publish(Event::CategoryCreated($categoryIdentifier->getId()));
            });
            
            return new CompositeRegion($categoryIdentifier, $includedCategoryIdentifiers, $excludedCategoryIdentifiers);
        }

        // TODO: Replace string $category by CategoryCategory $category.
        public function createGeographicalRegion(string $name, ?string $country, string $category, int $radius, mixed $geoJson, bool $overwrite) : GeographicalRegion {  
            $countryCategoryIdentifier = $country === null ? null : $this->getOrCreateCountryCategoryIdentifier($country);                                  
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category); 

            if ($countryCategoryIdentifier === null) {
                foreach ($this->getCategories(null, array(CategoryCategory::Country->value), array()) as &$invalidatedCategory) {
                    $this->eventPublisher->publish(Event::CategoryInvalidated($invalidatedCategory->getId()));
                }
            }
            else {
                $this->eventPublisher->publish(Event::CategoryInvalidated($countryCategoryIdentifier->getId()));
            }
            
            $geographicalRegion = new GeographicalRegion($categoryIdentifier, $countryCategoryIdentifier, $radius, $geoJson);
            $this->transactionManager->executeAtomically(function() use(&$categoryIdentifier, &$geographicalRegion, &$overwrite) {
                if ($overwrite) {
                    $this->categoryMapper->deleteGeographicalRegion($categoryIdentifier->getId());
                }
                // TODO: Block the insertion if the geographical region already exists. There is no unique constraint because of extensions.
                $this->categoryMapper->insertGeographicalRegion($geographicalRegion);    
                
                $this->eventPublisher->publish(Event::CategoryCreated($categoryIdentifier->getId()));
            });
            
            return $geographicalRegion;
        }

        // TODO: Replace string $category by CategoryCategory $category.
        public function createGeographicalRegionExtensionRegion(string $name, ?string $country, string $category, int $radius, mixed $geoJson) : GeographicalRegion {            
            $countryCategoryIdentifier = $country === null ? null : $this->getOrCreateCountryCategoryIdentifier($country);
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category);

            $geographicalRegion = new GeographicalRegion($categoryIdentifier, $countryCategoryIdentifier, $radius, $geoJson);
            $this->transactionManager->executeAtomically(function() use(&$geographicalRegion, &$country) {
                $this->categoryMapper->insertGeographicalRegion($geographicalRegion);

                // TODO: Improve by publishing an event that would invalidate categories only for the specific coordinates.
                if ($country === null) {
                    foreach ($this->getCategories(null, array(CategoryCategory::Country->value), array()) as &$category) {
                        $this->eventPublisher->publish(Event::CategoryInvalidated($category->getId()));
                    }
                }
                else {
                    $this->eventPublisher->publish(Event::CategoryInvalidated($this->getCategoryIdentifier($country)->getId()));
                }
            });

            return $geographicalRegion;
        }

        public function updateRegionAreas() : void {            
            $regionAreas = array();

            // Include geographical region.
            foreach ($this->categoryMapper->selectAllNonTrivialGeographicalRegions() as &$geographicalRegion) {
                $area = $this->getGeometry($geographicalRegion->getGeoJson())->getArea();
                $regionAreas[$geographicalRegion->getCategory()->getId()] = $area;

                // Include country regions.
                if ($geographicalRegion->getCountryCategory()?->getId() !== null) {
                    if (!array_key_exists($geographicalRegion->getCountryCategory()->getId(), $regionAreas)) {
                        $regionAreas[$geographicalRegion->getCountryCategory()->getId()] = 0;
                    }
                    $regionAreas[$geographicalRegion->getCountryCategory()?->getId()] += $area;
                }
            }

            // Include composite regions.
            foreach ($this->categoryMapper->selectCompositeRegions(null) as &$compositeRegion) {
                $compositeRegionArea = 0;

                foreach ($compositeRegion->getIncludedCategories() as &$includedCategory) {
                    if (array_key_exists($includedCategory->getId(), $regionAreas)) {
                        $compositeRegionArea += $regionAreas[$includedCategory->getId()];
                    }
                }

                foreach ($compositeRegion->getExcludedCategories() as &$excludedCategory) {
                    if (array_key_exists($excludedCategory->getId(), $regionAreas)) {
                        $compositeRegionArea -= $regionAreas[$excludedCategory->getId()];
                    }
                }

                $regionAreas[$compositeRegion->getCategory()->getId()] = $compositeRegionArea;
            }

            // Persist the region areas.
            $this->transactionManager->executeAtomically(function() use(&$regionAreas) {
                $this->categoryMapper->deleteAllRegionAreas();
                foreach ($regionAreas as $categoryId => $area) {
                    $this->categoryMapper->insertRegionArea($categoryId, $area);
                }
            });
        }

        public function getAllNonTrivialGeographicalRegions() : array {
            return $this->categoryMapper->selectAllNonTrivialGeographicalRegions();
        }

        public function getOrCreateCountryCategoryIdentifier(string $country) : CategoryIdentifier {
            return $this->getOrCreateCategoryIdentifier($country, CategoryCategory::Country->value);
        }

        public function removeCategory(string $categoryId) : void {
            $this->categoryMapper->deleteGeographicalRegion($categoryId);
            $this->categoryMapper->deleteCompositeRegion($categoryId);
            $this->categoryMapper->deleteCompositeRegionReferences($categoryId);
            $this->categoryMapper->deleteCategoryIdentifier($categoryId);
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
            if ($categoryIdentifier !== null) {
                return $categoryIdentifier;
            }

            $categoryIdentifier = new CategoryIdentifier(null, $name, CategoryCategory::from($category), null, null);
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

        private function getGeometry(mixed $geoJson) : mixed {
            return \geoPHP::load(json_encode($geoJson), "json");
        }

        private function isPointInPolygon(mixed $geoJson, mixed $point) : bool {
            $geometry = $this->getGeometry($geoJson);

            if (method_exists($geometry, "pointInPolygon")) {
                return $geometry->pointInPolygon($point);
            }

            if (method_exists($geometry, "getComponents")) {
                $pointInPolygon = false;
                foreach ($geometry->getComponents() as &$component) {
                    if ($component->pointInPolygon($point, $pointInPolygon)) {
                        $pointInPolygon = true;
                    }
                }
                return $pointInPolygon;
            }

            return $geometry->equals($point);
        }

        private function arrayAny(array $array, mixed $fn) : bool {
            foreach ($array as &$value) {
                if ($fn($value)) {
                    return true;
                }
            }
            return false;
        }
    
        private function arrayEvery(array $array, mixed $fn) : bool {
            foreach ($array as &$value) {
                if (!$fn($value)) {
                    return false;
                }
            }
            return true;
        }

        private function getCategoryIdentifierCacheKey(string $categoryId) : string {
            return sprintf(self::CATEGORY_IDENTIFIER_CACHE_KEY_FORMAT, $categoryId);
        }
    }
?>