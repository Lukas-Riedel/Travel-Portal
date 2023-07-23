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

        public function getname() {
            return $this->name;
        }

        public function getaddress() {
            return $this->address;
        }

        public function getstart() {
            return $this->start;
        }

        public function getend() {
            return $this->end;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>