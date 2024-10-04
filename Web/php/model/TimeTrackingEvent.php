<?php
    class TimeTrackingEvent implements JsonSerializable {        
        private $id;
        private $description;
        private $hours;
        private $timestamp;
        private $type;
        private $balance;

        public function __construct($id, $description, $hours, $timestamp, $type, $balance) {
            $this->id = $id;
            $this->description = $description;
            $this->hours = $hours;
            $this->timestamp = $timestamp;
            $this->type = $type;
            $this->balance = $balance;
        }

        public function getId() {
            return $this->id;
        }

        public function getDescription() {
            return $this->description;
        }

        public function getHours() {
            return $this->hours;
        }

        public function getTimestamp() {
            return $this->timestamp;
        }
        
        public function getType() {
            return $this->type;
        }

        public function getBalance() {
            return $this->balance;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>