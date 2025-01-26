<?php
    class CompositeRegion implements JsonSerializable {        
        private $categoryId;
        private $includedCategoryIds;
        private $excludedCategoryIds;

        public function __construct($categoryId, $includedCategoryIds, $excludedCategoryIds) {
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