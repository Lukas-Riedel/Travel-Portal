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

        public function getId() : int {
            return $this->id;
        }

        public function setId($id) : void {
            $this->id = $id;
        }

        public function getDescription() : string {
            return $this->description;
        }

        public function getHours() : float {
            return $this->hours;
        }

        public function getTimestamp() : int {
            return $this->timestamp;
        }
        
        public function getType() : string {
            return $this->type;
        }

        public function getBalance() : float {
            return $this->balance;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>