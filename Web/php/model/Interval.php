<?php
    class Interval implements JsonSerializable {
        private $start;
        private $end;

        public function __construct($start, $end) {
            $this->start = $start;
            $this->end = $end;
        }

        public function getStart() {
            return $this->start;
        }

        public function getEnd() {
            return $this->end;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>