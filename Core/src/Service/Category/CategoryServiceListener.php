<?php
    namespace Core\Service\Category;

    use Core\Common\CommonConstants;
    use Core\Service\Highlight\HighlightType;
    use Core\Service\Place\PlaceService;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;
    use Monolog\Logger;

    class CategoryServiceListener {
        
        private const UPDATE_CATEGORY_STATISTICS_ACTION_NAME = "UPDATE_CATEGORY_STATISTICS";
        private const UPDATE_CATEGORY_STATISTICS_ACTION_INTERVAL = 21 * CommonConstants::ONE_DAY_SECONDS;

        private readonly CategoryService $categoryService;
        private readonly PlaceService $placeService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;
        private readonly Logger $logger;

        private readonly int $maxHighlightsPerCategoryCount;

        public function __construct(CategoryService $categoryService, PlaceService $placeService, EventPublisher $eventPublisher,
            Scheduler $scheduler, Logger $logger, int $maxHighlightsPerCategoryCount) {
            $this->categoryService = $categoryService;
            $this->placeService = $placeService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->logger = $logger;
            $this->maxHighlightsPerCategoryCount = $maxHighlightsPerCategoryCount;
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
                $category = $this->categoryService->getCategory($message["entityId"]);
                if ($category !== null) {
                    if ($category->getMainHighlight() === null) {
                        $this->categoryService->updateCategoryMainHighlight($message["entityId"], $message["highlightId"]);
                    }
                }

                if (count($category->getHighlights()) !== $this->maxHighlightsPerCategoryCount) {
                    $this->categoryService->refreshCategoryHighlights($message["entityId"], $this->maxHighlightsPerCategoryCount);                        
                }
            }
        }

        public function onHighlightRemoved(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Category->value) {
                $category = $this->categoryService->getCategory($message["entityId"]);
                if ($category !== null) {
                    if ($category->getMainHighlight() === null || $category->getMainHighlight()->getId() === $message["highlightId"]) {
                        if (count($category->getHighlights()) > 0) {
                            $this->categoryService->updateCategoryMainHighlight($category->getId(), $category->getHighlights()[0]->getId());
                        } 
                        else {
                            $this->categoryService->updateCategoryMainHighlight($category->getId(), null);
                        }
                    }
                    
                    if (count($category->getHighlights()) !== $this->maxHighlightsPerCategoryCount) {
                        $this->logger->debug("There are " . count($category->getHighlights()) . "/" . $this->maxHighlightsPerCategoryCount . " highlights for the '" . $message["entityId"] . "' category. Refreshing the highlights...");
                        $this->categoryService->refreshCategoryHighlights($message["entityId"], $this->maxHighlightsPerCategoryCount);                        
                    }
                }
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