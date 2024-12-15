<?php
    class Stay implements JsonSerializable {        
        private $name;
        private $address;
        private $start;
        private $end;

        public function __construct($name, $address, $start, $end) {
            $this->name = $name;
            $this->address = $address;
            $this->start = $start;
            $this->end = $end;
        }

        public function getName() : string {
            return $this->name;
        }

        public function getAddress() : string {
            return $this->address;
        }

        public function getStart() : int {
            return $this->start;
        }

        public function getEnd() : int {
            return $this->end;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>