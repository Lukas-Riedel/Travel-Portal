<?php
    namespace Service\Service\Category;
    
    class CompositeRegion implements \JsonSerializable {        
        private readonly string $categoryId;
        private readonly array $includedCategoryIds;
        private readonly array $excludedCategoryIds;

        public function __construct(string $categoryId, array $includedCategoryIds, array $excludedCategoryIds) {
            $this->categoryId = $categoryId;
            $this->includedCategoryIds = $includedCategoryIds;
            $this->excludedCategoryIds = $excludedCategoryIds;
        }

        public function getCategoryId() : string {
            return $this->categoryId;
        }

        public function getIncludedCategoryIds() : array {
            return $this->includedCategoryIds;
        }

        public function getExcludedCategoryIds() : array {
            return $this->excludedCategoryIds;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>