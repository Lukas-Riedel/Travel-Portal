<?php
    namespace Service\Service\Category;
    
    use Service\Service\Highlight\HighlightType;
    use Service\Service\Place\PlaceIdentifier;

    class CategoryServiceListener {
        
        private const UPDATE_CATEGORY_STATISTICS_ACTION_NAME = "UPDATE_CATEGORY_STATISTICS";
        private const UPDATE_CATEGORY_STATISTICS_ACTION_INTERVAL = 604800;

        private readonly CategoryService $categoryService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(CategoryService $categoryService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->categoryService = $categoryService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onCategoryCreated(mixed $message) : void {
            $this->categoryService->updateRegionAreas();
        }

        public function onPlaceUpdated(mixed $message) : void {            
            $this->categoryService->updateCategories(new PlaceIdentifier($message["placeIdentifier"]["id"], $message["placeIdentifier"]["name"],
                $message["placeIdentifier"]["country"], $message["placeIdentifier"]["latitude"], $message["placeIdentifier"]["longitude"],
                $message["placeIdentifier"]["timezone"], $message["placeIdentifier"]["mainHighlight"], $message["placeIdentifier"]["excerpt"]));
        }

        public function onHighlightCreated(mixed $message) : void {
            if ($message["highlightType"] === HighlightType::Category->name) {
                $categoryIdentifier = $this->categoryService->getCategoryIdentifierById($message["entityId"]);
                if ($categoryIdentifier !== NULL && $categoryIdentifier->getMainHighlight() === NULL) {
                    $this->categoryService->updateCategoryMainHighlight($message["entityId"], $message["highlightId"]);
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($message["action"] === self::UPDATE_CATEGORY_STATISTICS_ACTION_NAME 
                && $message["timeSinceLastExecution"] > self::UPDATE_CATEGORY_STATISTICS_ACTION_INTERVAL) {
                $categories = $this->categoryService->getCategories(CategoryCategory::values(), array());
                foreach ($categories as &$category) {
                    $this->eventPublisher->publishCategoryStatisticsInvalidatedEvent($category->getId());
                }                        
                $this->scheduler->recordEventsTriggered(self::UPDATE_CATEGORY_STATISTICS_ACTION_NAME);
            }
        }
    }
?>