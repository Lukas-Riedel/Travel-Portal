<?php
    namespace Core\Service\Category;

    use Core\Common\CommonConstants;
    use Core\Service\Highlight\HighlightType;
    use Core\Service\Place\PlaceService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class CategoryServiceListener {
        
        private const UPDATE_CATEGORY_STATISTICS_ACTION_NAME = "UPDATE_CATEGORY_STATISTICS";
        private const UPDATE_CATEGORY_STATISTICS_ACTION_INTERVAL = 21 * CommonConstants::ONE_DAY_SECONDS;

        private readonly CategoryService $categoryService;

        private readonly PlaceService $placeService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(CategoryService $categoryService, PlaceService $placeService, EventPublisher $eventPublisher, Scheduler $scheduler) {
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
            if ($message["highlightType"] === HighlightType::Category->value) {
                $categoryIdentifier = $this->categoryService->getCategoryIdentifierById($message["entityId"]);
                if ($categoryIdentifier !== null && $categoryIdentifier->getMainHighlight() === null) {
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
            if ($this->scheduler->requestExecution(self::UPDATE_CATEGORY_STATISTICS_ACTION_NAME, self::UPDATE_CATEGORY_STATISTICS_ACTION_INTERVAL)) {
                $categories = $this->categoryService->getCategories(null, CategoryCategory::values(), array());
                foreach ($categories as &$category) {
                    $this->eventPublisher->publish(Event::CategoryStatisticsInvalidated($category->getId()));
                }
            }
        }
    }
?>