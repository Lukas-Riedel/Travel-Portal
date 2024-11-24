<?php
    require_once(dirname(__FILE__) . "/Highlight.php");

    class TripIdentifier implements JsonSerializable {        
        private $id;
        private $name;
        private $year;
        private $mainHighlight;

        public function __construct($id, $name, $year, $mainHighlight) {
            $this->id = $id;
            $this->name = $name;
            $this->year = $year;
            $this->mainHighlight = $mainHighlight;
        }

        public function getId() : int {
            return $this->id;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getYear() : int {
            return $this->year;
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