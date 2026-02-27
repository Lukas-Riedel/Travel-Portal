<?php
    namespace Core\Service\Category;

    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;
    use Core\Service\Index\IndexType;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceSortingStrategy;

    class CategoryIndexer implements EntityIndexer {

        private readonly CategoryService $categoryService;

        private readonly PlaceService $placeService;

        public function __construct(CategoryService $categoryService, PlaceService $placeService) {
            $this->categoryService = $categoryService;
            $this->placeService = $placeService;
        }

        public function index(IndexType $indexType, IndexableEntityType $entityType, ?string $entityId) : array {
            $result = array();

            if ($indexType === IndexType::Composite && $entityType === IndexableEntityType::Category) {
                $categories = $entityId !== null
                    ? array($this->categoryService->getCategory($entityId))
                    : $this->categoryService->getCategories(null, CategoryCategory::values(), array());

                foreach ($categories as &$category) {
                    $categoryPlaces = $this->placeService->getRegularPlaces($category->getId(), null, null, null, null, null, null, null, time(), null, null, array(), PlaceSortingStrategy::OldestAscending);
                    if (count($categoryPlaces) > 0) {
                        $result[$category->getId()] = array($category->getName());                        
                    }
                }
            }

            return $result;
        }
    }
?>