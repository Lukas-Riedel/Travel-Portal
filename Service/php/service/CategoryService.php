<?php
    require_once(dirname(__FILE__) . "/CategoryMapper.php");
    require_once(dirname(__FILE__) . "/../model/CategoryIdentifier.php");
    require_once(dirname(__FILE__) . "/../model/Category.php");
    require_once(dirname(__FILE__) . "/../model/GeographicalRegion.php");
    require_once(dirname(__FILE__) . "/../model/CompositeRegion.php");

    class CategoryService {

        private const CIRCLE_APPROXIMATION_POINTS_COUNT = 10;

        private readonly CategoryMapper $categoryMapper;

        private readonly ConfigurationService $configurationService;
        private readonly EventPublisher $eventPublisher;

        public function __construct(DatabaseProvider $databaseProvider, ConfigurationService $configurationService,
            HighlightService $highlightService, StatisticsService $statisticsService, EventPublisher $eventPublisher) {
            $this->categoryMapper = new CategoryMapper($databaseProvider, $highlightService, $statisticsService);
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
        }

        public function updateCategories(PlaceIdentifier $placeIdentifier) : void {
            $categoryIds = array();
            
            // Include country category.
            $categoryIds[] = $this->getOrCreateCategoryIdentifier($placeIdentifier->getCountry(), CategoryCategory::Country->value)->getId(); 
        
            // Include geographical region categories.
            $point = $this->getWktPoint($placeIdentifier->getLatitude(), $placeIdentifier->getLongitude());
            foreach ($this->categoryMapper->selectAllGeographicalRegions() as &$geographicalRegion) {
                if ($geographicalRegion->getCountry() === NULL || $geographicalRegion->getCountry() === $placeIdentifier->getCountry()) {
                    if ($this->isPointInPolygon($geographicalRegion->getGeoJson(), $point)) {
                        $categoryIds[] = $geographicalRegion->getCategoryId();
                    }
                    else if ($geographicalRegion->getRadius() > 0) {
                        foreach ($this->getWktPointsOnCircle($placeIdentifier->getLongitude(), $placeIdentifier->getLatitude(),
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
                if ($this->arrayAny($compositeRegion->getIncludedCategoryIds(), function ($includedCategoryId)
                        use (&$categoryIds) { return in_array($includedCategoryId, $categoryIds); })
                    && $this->arrayEvery($compositeRegion->getExcludedCategoryIds(), function ($excludedCategoryId)
                        use (&$categoryIds) { return !in_array($excludedCategoryId, $categoryIds); })) {
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

        public function getCategoryIdentifierByName(string $name) : ?CategoryIdentifier { 
            return $this->categoryMapper->selectCategoryIdentifierByName($name);
        }

        public function getCategoryIdentifier(string $categoryId) : ?CategoryIdentifier {
            return $this->categoryMapper->selectCategoryIdentifier($categoryId);
        }

        public function getCategoryIdentifiers(array $categoryIds) : array { 
            $categories = array();

            foreach ($categoryIds as &$categoryId) {
                $category = $this->getCategoryIdentifier($categoryId);
                if ($category !== NULL) {
                    $categories[] = $category;
                }
            }
            
            return $categories;
        }

        public function getCategory(string $categoryId) : ?Category {
            $categories = $this->categoryMapper->selectCategories($categoryId, CategoryCategory::values(), CategoryIncludedEntity::values());
            return count($categories) === 1 ? $categories[0] : NULL;
        }

        public function getCategories(array $categoryCategories, array $includedEntities) : array {
            return $this->categoryMapper->selectCategories(NULL, $categoryCategories, $includedEntities);
        }

        public function updateCategoryMainHighlight(string $categoryId, string $highlightIdentifier) : bool {
            $wasUpdated = $this->categoryMapper->updateCategoryMainHighlight($categoryId, $highlightIdentifier);
            
            if ($wasUpdated) {
                $this->eventPublisher->publishCategoryUpdatedEvent($categoryId);
            }

            return $wasUpdated;
        }

        public function updateCategoryName(string $categoryId, string $name) : bool {            
            $wasUpdated = $this->categoryMapper->updateCategoryName($categoryId, $name);
            
            if ($wasUpdated) {
                $this->eventPublisher->publishCategoryUpdatedEvent($categoryId);
            }

            return $wasUpdated;
        }
        
        // TODO: Replace string $category by CategoryCategory $category.
        public function getOrCreateCategoryIdentifier(string $name, string $category) : CategoryIdentifier {
            $categoryIdentifier = $this->getCategoryIdentifierByName($name);
            if ($categoryIdentifier !== NULL) {
                return $categoryIdentifier;
            }

            $categoryIdentifier = new CategoryIdentifier(NULL, $name, $category, NULL);
            $this->categoryMapper->insertCategoryIdentifier($categoryIdentifier);

            return $categoryIdentifier;
        }

        // TODO: Replace string $category by CategoryCategory $category.
        public function createCompositeRegion(string $name, string $category, array $includedRegions, array $excludedRegions) : CategoryIdentifier {
            // Find out what can the composite regions consist of.
            $referencableRegionNames = $this->categoryMapper->selectAllCategoryNames();
            
            foreach ($this->configurationService->getConfigurationKeysForType("countries") as $countryName) {
                if (!in_array($countryName, $referencableRegionNames)) {
                    $referencableRegionNames[] = $countryName;
                }
            }

            // Verify that all referenced regions exist.
            foreach ($includedRegions as &$includedRegion) {
                if (!in_array($includedRegion, $referencableRegionNames)) {
                    throw new InvalidArgumentException("The included region '" . $includedRegion . "' does not exist.");
                }
            }

            foreach ($excludedRegions as &$excludedRegion) {
                if (!in_array($excludedRegion, $referencableRegionNames)) {
                    throw new InvalidArgumentException("The excluded region '" . $excludedRegion . "' does not exist.");
                }
            }

            // Create the region.
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category);
            $this->categoryMapper->deleteCompositeRegion($categoryIdentifier->getId());

            foreach ($includedRegions as &$includedRegion) {
                $subjectCategoryIdentifier = $this->getCategoryIdentifierByName($includedRegion);
                $this->categoryMapper->insertCompositeRegionInclusion($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId());
                $this->eventPublisher->publishCategoryInvalidatedEvent($subjectCategoryIdentifier->getId());
            }

            foreach ($excludedRegions as &$excludedRegion) {
                $subjectCategoryIdentifier = $this->getCategoryIdentifierByName($excludedRegion);
                $this->categoryMapper->insertCompositeRegionExclusion($categoryIdentifier->getId(), $subjectCategoryIdentifier->getId());
            }
    
            $this->eventPublisher->publishCategoryCreatedEvent($categoryIdentifier->getId());
            
            return $categoryIdentifier;
        }

        // TODO: Replace string $category by CategoryCategory $category.
        public function createGeographicalRegion(string $name, ?string $country, string $category, int $radius, mixed $geoJson) : CategoryIdentifier {                                    
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category); 
            $this->categoryMapper->deleteGeographicalRegion($categoryIdentifier->getId(), $country);
            $this->categoryMapper->insertGeographicalRegion(new GeographicalRegion($categoryIdentifier->getId(), $country, $radius, $geoJson));

            if ($country === NULL) {
                foreach ($this->getCategories(array(CategoryCategory::Country->value), array()) as &$category) {
                    $this->eventPublisher->publishCategoryInvalidatedEvent($category->getId());
                }
            }
            else {
                $this->eventPublisher->publishCategoryInvalidatedEvent($this->getCategoryIdentifierByName($country)->getId());
            }
    
            $this->eventPublisher->publishCategoryCreatedEvent($categoryIdentifier->getId());
            
            return $categoryIdentifier;
        }

        // TODO: Replace string $category by CategoryCategory $category.
        public function createGeographicalRegionExtensionRegion(string $name, string $country, string $category, float $latitude, float $longitude) : CategoryIdentifier {
            $geoJson = json_encode(array(
                "type" => "Feature", 
                "geometry" => array(
                    "type" => "Point", 
                    "coordinates" => array(
                        floatval($longitude), 
                        floatval($latitude)))), TRUE);
            
            $categoryIdentifier = $this->getOrCreateCategoryIdentifier($name, $category);
            $this->categoryMapper->insertGeographicalRegion(new GeographicalRegion($categoryIdentifier->getId(), $country, 0, $geoJson));

            // TODO: Improve by publishing an event that would invalidate categories only for the specific coordinates.
            if ($country === NULL) {
                foreach ($this->getCategories(array(CategoryCategory::Country->value), array()) as &$category) {
                    $this->eventPublisher->publishCategoryInvalidatedEvent($category->getId());
                }
            }
            else {
                $this->eventPublisher->publishCategoryInvalidatedEvent($this->getCategoryIdentifierByName($country)->getId());
            }

            return $categoryIdentifier;
        }

        public function updateRegionAreas() : void {            
            $regionAreas = array();

            // Include geographical region.
            foreach ($this->categoryMapper->selectAllNonTrivialGeographicalRegions() as &$geographicalRegion) {
                $area = $geographicalRegion->getGeoJson()->getArea();
                $regionAreas[$geographicalRegion->getCategoryId()] = $area;

                if ($geographicalRegion->getCountry() !== NULL) {
                    $countryCategoryId = $this->getCategoryIdentifierByName($geographicalRegion->getCountry());

                    // Include country regions.
                    if ($countryCategoryId !== NULL) {
                        if (!array_key_exists($countryCategoryId->getId(), $regionAreas)) {
                            $regionAreas[$countryCategoryId->getId()] = 0;
                        }
                        $regionAreas[$countryCategoryId->getId()] += $area;
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

        private function getWktPointsOnCircle(float $x, float $y, int $radiusInKms, int $pointsCount) : array {    
            $points = array();
    
            for ($i = 0; $i < $pointsCount; $i++) {
                $points[] = $this->getWktPoint(
                    $y + $this->positionY($pointsCount, $i, $radiusInKms / 111), 
                    $x + $this->positionX($pointsCount, $i, $radiusInKms / 111)
                );
            }
    
            return $points;
        }
    
        private function positionX(int $count, int $index, float $radius) : float {
            $alpha = 360 / $count;
            $angle = $alpha * $index;
            $x = $radius * cos(deg2rad($angle));
            return $x;
        }
          
        private function positionY(int $count, int $index, float $radius) : float {
            $alpha = 360 / $count;
            $angle = $alpha * $index;
            $y = $radius * sin(deg2rad($angle));
            return $y;
        }

        private function getWktPoint(float $latitude, float $longitude) : mixed {
            return geoPHP::load("POINT (" . $longitude . " " . $latitude . ")", "wkt");
        }

        private function isPointInPolygon(mixed $geoJson, mixed $point) : bool {
            if (method_exists($geoJson, "pointInPolygon")) {
                return $geoJson->pointInPolygon($point);
            }

            if (method_exists($geoJson, "getComponents")) {
                $pointInPolygon = FALSE;
                foreach ($geoJson->getComponents() as &$component) {
                    $pointInPolygon = $component->pointInPolygon($point, $pointInPolygon);
                }
                return $pointInPolygon;
            }

            return $geoJson->centroid() == $point->centroid();
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

        public function onCategoryCreated($message) : void {
            $this->updateRegionAreas();
        }

        public function onPlaceUpdated($message) : void {            
            $this->updateCategories(new PlaceIdentifier($message["placeIdentifier"]["id"], $message["placeIdentifier"]["name"],
                $message["placeIdentifier"]["country"], $message["placeIdentifier"]["latitude"], $message["placeIdentifier"]["longitude"],
                $message["placeIdentifier"]["timezone"], $message["placeIdentifier"]["mainHighlight"], $message["placeIdentifier"]["excerpt"]));
        }
    }

    enum CategoryIncludedEntity : string {
        case Statistics = "STATISTICS";
        case Highlights = "HIGHLIGHTS";
        
        public static function values(): array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }

    enum CategoryCategory : string {
        case Continent = "CONTINENT";
        case Country = "COUNTRY";
        case Administrative = "ADMINISTRATIVE";
        case Ocean = "OCEAN";
        case Sea = "SEA";
        case Bay = "BAY";
        case Variable = "VARIABLE";
        case Island = "ISLAND";
        case Region = "REGION";

        public static function values(): array {
            return array_map(fn($case) => $case->value, self::cases());
        }
    }
?>