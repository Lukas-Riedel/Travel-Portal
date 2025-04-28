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
        private readonly ?int $delay;

        public function __construct(string $flight, ?string $registration, ?string $aircraft,
            ?float $distance, Airport $from, Airport $to, int $start, int $end, ?int $delay) {
            $this->flight = $flight;
            $this->registration = $registration;
            $this->aircraft = $aircraft;
            $this->distance = $distance;
            $this->from = $from;
            $this->to = $to;
            $this->start = $start;
            $this->end = $end;
            $this->delay = $delay;
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

        public function getDelay() : ?int {
            return $this->delay;
        }

        public function getDuration() : int {
            return $this->end - $this->start;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>