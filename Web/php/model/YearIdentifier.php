<?php
    class YearIdentifier implements JsonSerializable {        
        private $id;
        private $mainHighlight;

        public function __construct($id, $mainHighlight) {
            $this->id = $id;
            $this->mainHighlight = $mainHighlight;
        }

        public function getId() {
            return $this->id;
        }

        public function getMainHighlight() {
            return $this->mainHighlight;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>