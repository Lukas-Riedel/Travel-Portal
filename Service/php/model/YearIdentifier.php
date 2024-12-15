<?php
    require_once(dirname(__FILE__) . "/Highlight.php");

    class YearIdentifier implements JsonSerializable {        
        private $id;
        private $mainHighlight;

        public function __construct($id, $mainHighlight) {
            $this->id = $id;
            $this->mainHighlight = $mainHighlight;
        }

        public function getId() : int {
            return $this->id;
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