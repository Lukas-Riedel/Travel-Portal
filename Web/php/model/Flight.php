<?php
    class Flight implements JsonSerializable {        
        private $flight;
        private $registration;
        private $aircraft;
        private $distance;
        private $from;
        private $to;
        private $start;
        private $end;

        public function __construct($flight, $registration, $aircraft, $distance, $from, $to, $start, $end) {
            $this->flight = $flight;
            $this->registration = $registration;
            $this->aircraft = $aircraft;
            $this->distance = $distance;
            $this->from = $from;
            $this->to = $to;
            $this->start = $start;
            $this->end = $end;
        }

        public function getFlight() {
            return $this->flight;
        }

        public function getRegistration() {
            return $this->registration;
        }

        public function getAircraft() {
            return $this->aircraft;
        }

        public function getDistance() {
            return $this->distance;
        }

        public function getFrom() {
            return $this->from;
        }

        public function getTo() {
            return $this->to;
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