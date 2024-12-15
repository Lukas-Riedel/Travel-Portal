<?php
    class TripDays implements JsonSerializable {
        private $total;
        private $working;

        public function __construct($total, $working) {
            $this->total = $total;
            $this->working = $working;
        }

        public function getTotal() : int {
            return $this->total;
        }

        public function getWorking() : int {
            return $this->working;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>