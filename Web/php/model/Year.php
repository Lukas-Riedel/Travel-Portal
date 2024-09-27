<?php
    class Year implements JsonSerializable {        
        private $id;
        private $stats;

        public function __construct($id, $stats) {
            $this->id = $id;
            $this->stats = $stats;
        }

        public function getId() {
            return $this->id;
        }

        public function getStats() {
            return $this->stats;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>