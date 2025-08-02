<?php
    namespace Service\Service\Category;
    
    use Service\Service\Highlight\Highlight;

    class CategoryIdentifier implements \JsonSerializable {        
        private ?string $id;
        private readonly string $name;
        private readonly CategoryCategory $category;
        private readonly ?CategoryMetadata $metadata;
        private readonly ?Highlight $mainHighlight;

        public function __construct(?string $id, string $name, CategoryCategory $category,
            ?CategoryMetadata $metadata, ?Highlight $mainHighlight) {
            $this->id = $id;
            $this->name = $name;
            $this->category = $category;
            $this->metadata = $metadata;
            $this->mainHighlight = $mainHighlight;
        }

        public function getId() : string {
            return $this->id;
        }

        public function setId(string $id) : void {
            $this->id = $id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCategory() : ?CategoryCategory {
            return $this->category;
        }

        public function getMetadata() : ?CategoryMetadata {
            return $this->metadata;
        }

        public function getMainHighlight() : ?Highlight {
            return $this->mainHighlight;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>