<?php
    require_once(dirname(__FILE__) . "/Highlight.php");
    require_once(dirname(__FILE__) . "/CategoryMetadata.php");

    class CategoryIdentifier implements JsonSerializable {        
        private $id;
        private $name;
        private $category;
        private $metadata;
        private $mainHighlight;

        public function __construct($id, $name, $category, $metadata, $mainHighlight) {
            $this->id = $id;
            $this->name = $name;
            $this->category = $category;
            $this->metadata = $metadata;
            $this->mainHighlight = $mainHighlight;
        }

        public function getId() : int {
            return $this->id;
        }

        public function setId($id) : void {
            $this->id = $id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getCategory() : string {
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