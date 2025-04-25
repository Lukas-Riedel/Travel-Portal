<?php
    namespace Service\Service\Flight;

    class Flight implements \JsonSerializable {        
        private readonly string $flight;
        private readonly ?string $registration;
        private readonly ?string $aircraft;
        private readonly ?float $distance;
        private readonly Airport $from;
        private readonly Airport $to;
        private readonly int $start;
        private readonly int $end;

        public function __construct(string $flight, ?string $registration, ?string $aircraft,
            ?float $distance, Airport $from, Airport $to, int $start, int $end) {
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