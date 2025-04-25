<?php

    namespace Service\Service\Fitness;

    class Fitness implements \JsonSerializable {        
        private readonly int $steps;
        private readonly int $minutes;
        private readonly float $calories;
        private readonly float $distance;

        public function __construct(int $steps, int $minutes, float $calories, float $distance) {
            $this->steps = $steps;
            $this->minutes = $minutes;
            $this->calories = $calories;
            $this->distance = $distance;
        }

        public function getSteps() : int {
            return $this->steps;
        }

        public function getMinutes() : int {
            return $this->minutes;
        }

        public function getCalories() : float {
            return $this->calories;
        }

        public function getDistance() : float {
            return $this->distance;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>