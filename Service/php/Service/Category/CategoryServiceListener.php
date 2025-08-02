<?php
    namespace Service\Service\Category;
    
    use Service\Service\Highlight\HighlightType;
    use Service\Service\Place\PlaceService;

    class CategoryServiceListener {
        
        private const UPDATE_CATEGORY_STATISTICS_ACTION_NAME = "UPDATE_CATEGORY_STATISTICS";
        private const UPDATE_CATEGORY_STATISTICS_ACTION_INTERVAL = 86400 * 14;

        private readonly CategoryService $categoryService;

        private readonly PlaceService $placeService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(CategoryService $categoryService, PlaceService $placeService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->categoryService = $categoryService;
            $this->placeService = $placeService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onCategoryCreated(mixed $message) : void {
            $this->categoryService->updateRegionAreas();
        }

        public function onPlaceCreated(mixed $message) : void {            
            $this->categoryService->updateCategories($this->placeService->getPlaceIdentifierById($message["placeId"]));
        }

        public function onPlaceUpdated(mixed $message) : void {            
            $this->categoryService->updateCategories($this->placeService->getPlaceIdentifierById($message["placeId"]));
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Category->name) {
                $categoryIdentifier = $this->categoryService->getCategoryIdentifierById($message["entityId"]);
                if ($categoryIdentifier !== NULL && $categoryIdentifier->getMainHighlight() === NULL) {
                    $this->categoryService->updateCategoryMainHighlight($message["entityId"], $message["highlightId"]);
                }
            }
        }

        public function onConfigurationEntryUpdated(mixed $message) : void {
            if ($message["key"] === "countryNames") {
                $this->categoryService->removeStaleCategoryIdentifiers();
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::UPDATE_CATEGORY_STATISTICS_ACTION_NAME 
                && time() - $message["lastTriggered"] > self::UPDATE_CATEGORY_STATISTICS_ACTION_INTERVAL) {
                $categories = $this->categoryService->getCategories(NULL, CategoryCategory::values(), array());
                foreach ($categories as &$category) {
                    $this->eventPublisher->publishCategoryStatisticsInvalidatedEvent($category->getId());
                }                        
                $this->scheduler->recordEventsTriggered(self::UPDATE_CATEGORY_STATISTICS_ACTION_NAME);
            }
        }
    }
?>