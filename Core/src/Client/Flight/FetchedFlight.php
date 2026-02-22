<?php
    namespace Core\Client\Flight;

    class FetchedFlight implements \JsonSerializable {        
        private readonly string $flight;
        private readonly string $registration;
        private readonly string $aircraft;
        private readonly string $fromCode;
        private readonly string $toCode;
        private readonly int $scheduledDeparture;
        private readonly ?int $estimatedDeparture;
        private readonly ?int $actualDeparture;
        private readonly int $scheduledArrival;
        private readonly ?int $estimatedArrival;
        private readonly ?int $actualArrival;

        public function __construct(string $flight, string $registration, string $aircraft, string $fromCode, string $toCode,
            int $scheduledDeparture, ?int $estimatedDeparture, ?int $actualDeparture, int $scheduledArrival, ?int $estimatedArrival, ?int $actualArrival) {
            $this->flight = $flight;
            $this->registration = $registration;
            $this->aircraft = $aircraft;
            $this->fromCode = $fromCode;
            $this->toCode = $toCode;
            $this->scheduledDeparture = $scheduledDeparture;
            $this->estimatedDeparture = $estimatedDeparture;
            $this->actualDeparture = $actualDeparture;
            $this->scheduledArrival = $scheduledArrival;
            $this->estimatedArrival = $estimatedArrival;
            $this->actualArrival = $actualArrival;
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

        public function getFromCode() : string {
            return $this->fromCode;
        }

        public function getToCode() : string {
            return $this->toCode;
        }

        public function getScheduledDeparture() : int {
            return $this->scheduledDeparture;
        }

        public function getEstimatedDeparture() : ?int {
            return $this->estimatedDeparture;
        }

        public function getActualDeparture() : ?int {
            return $this->actualDeparture;
        }

        public function getScheduledArrival() : int {
            return $this->scheduledArrival;
        }

        public function getEstimatedArrival() : ?int {
            return $this->estimatedArrival;
        }

        public function getActualArrival() : ?int {
            return $this->actualArrival;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>