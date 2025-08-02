<?php

    namespace Service\Service\Fitness;

    class Fitness implements \JsonSerializable {        
        private readonly int $steps;
        private readonly int $seconds;
        private readonly float $calories;
        private readonly float $distance;

        public function __construct(int $steps, int $seconds, float $calories, float $distance) {
            $this->steps = $steps;
            $this->seconds = $seconds;
            $this->calories = $calories;
            $this->distance = $distance;
        }

        public function getSteps() : int {
            return $this->steps;
        }

        public function getSeconds() : int {
            return $this->seconds;
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