<?php

    namespace Service\Service\Fitness;

    class TimeBasedFitness implements \JsonSerializable {        
        private readonly int $timestamp;
        private readonly Fitness $fitness;

        public function __construct(int $timestamp, int $steps, int $seconds, float $calories, float $distance) {
            $this->timestamp = $timestamp;
            $this->fitness = new Fitness($steps, $seconds, $calories, $distance);
        }

        public function getTimestamp() : int {
            return $this->timestamp;
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