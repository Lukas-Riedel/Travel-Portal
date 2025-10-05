<?php
    namespace Core\Client\Flight;

    class FetchedFlight implements \JsonSerializable {        
        private readonly string $flight;
        private readonly string $registration;
        private readonly string $aircraft;
        private readonly string $fromCode;
        private readonly string $toCode;
        private readonly string $scheduledDeparture;
        private readonly string $actualDeparture;
        private readonly string $scheduledArrival;
        private readonly string $actualArrival;

        public function __construct(string $flight, string $registration, string $aircraft, string $fromCode, string $toCode,
            string $scheduledDeparture, string $actualDeparture, string $scheduledArrival, string $actualArrival) {
            $this->flight = $flight;
            $this->registration = $registration;
            $this->aircraft = $aircraft;
            $this->fromCode = $fromCode;
            $this->toCode = $toCode;
            $this->scheduledDeparture = $scheduledDeparture;
            $this->actualDeparture = $actualDeparture;
            $this->scheduledArrival = $scheduledArrival;
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

        public function getScheduledDeparture() : string {
            return $this->scheduledDeparture;
        }

        public function getActualDeparture() : string {
            return $this->actualDeparture;
        }

        public function getScheduledArrival() : string {
            return $this->scheduledArrival;
        }

        public function getActualArrival() : string {
            return $this->actualArrival;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>