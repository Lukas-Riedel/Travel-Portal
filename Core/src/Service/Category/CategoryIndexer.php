<?php
    namespace Core\Service\Category;

    use Core\Service\Index\EntityIndexer;
    use Core\Service\Index\IndexableEntityType;

    class CategoryIndexer implements EntityIndexer {

        private readonly CategoryService $categoryService;

        public function __construct(CategoryService $categoryService) {
            $this->categoryService = $categoryService;
        }

        public function index(IndexableEntityType $entityType) : array {
            $result = array();

            if ($entityType === IndexableEntityType::Category) {
                $categories = $this->categoryService->getCategories(null, CategoryCategory::values(), array());

                foreach ($categories as &$category) {
                    $result[$category->getId()] = array($category->getName());
                }
            }

            return $result;
        }
    }
?>