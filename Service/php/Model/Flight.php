<?php
    require_once(dirname(__FILE__) . "/Airport.php");

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

        public function getFlight() : string {
            return $this->flight;
        }

        public function getRegistration() : string {
            return $this->registration;
        }

        public function getAircraft() : string {
            return $this->aircraft;
        }

        public function getDistance() : float {
            return $this->distance;
        }

        public function getFrom() : Airport {
            return $this->from;
        }

        public function getTo() : Airport {
            return $this->to;
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