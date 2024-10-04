<?php
    class Fitness implements JsonSerializable {        
        private $steps;
        private $minutes;
        private $calories;
        private $distance;

        public function __construct($steps, $minutes, $calories, $distance) {
            $this->steps = $steps;
            $this->minutes = $minutes;
            $this->calories = $calories;
            $this->distance = $distance;
        }

        public function getSteps() {
            return $this->steps;
        }

        public function getMinutes() {
            return $this->minutes;
        }

        public function getCalories() {
            return $this->calories;
        }

        public function getDistance() {
            return $this->distance;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>