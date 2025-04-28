<?php
    namespace Service\Service\Fitness;

    use Service\Service\Trip\Trip;

    class TripFitness implements \JsonSerializable {
        private readonly Trip $trip;
        private readonly Fitness $fitness;

        public function __construct(Trip $trip, Fitness $fitness) {
            $this->trip = $trip;
            $this->fitness = $fitness;
        }

        public function getTrip() : Trip {
            return $this->trip;
        }

        public function getFitness() : Fitness {
            return $this->fitness;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>