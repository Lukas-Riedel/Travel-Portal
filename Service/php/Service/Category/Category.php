<?php
    namespace Service\Service\Category;

    use Service\Service\Highlight\Highlight;

    class Category implements \JsonSerializable {        
        private readonly string $id;
        private readonly string $name;
        private readonly CategoryCategory $category;
        private readonly ?CategoryMetadata $metadata;
        private readonly ?Highlight $mainHighlight;
        private readonly array $highlights;
        private readonly array $statistics;

        public function __construct(string $id, string $name, CategoryCategory $category, ?CategoryMetadata $metadata,
            ?Highlight $mainHighlight, array $highlights, array $statistics) {
            $this->id = $id;
            $this->name = $name;
            $this->category = $category;
            $this->metadata = $metadata;
            $this->mainHighlight = $mainHighlight;
            $this->highlights = $highlights;
            $this->statistics = $statistics;
        }

        public function getId() : string {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCategory() : CategoryCategory {
            return $this->category;
        }

        public function getMetadata() : ?CategoryMetadata {
            return $this->metadata;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        public function getHighlights() : array {
            return $this->highlights;
        }

        public function getStats() : array {
            return $this->statistics;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>