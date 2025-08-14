<?php
    namespace Core\Service\Place;

    use Core\Service\Category\CategoryIdentifier;

    class CategoryPlaces implements \JsonSerializable {
        private readonly CategoryIdentifier $category;
        private readonly array $places;

        public function __construct(CategoryIdentifier $category, array $places) {
            $this->category = $category;
            $this->places = $places;
        }

        public function getCategory() : CategoryIdentifier {
            return $this->category;
        }

        public function getPlaces() : array {
            return $this->places;
        }

        public function getPlacesCount() : int {
            return count($this->places);
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>