<?php
    namespace Service\Service\Forecast;

    class Interval implements \JsonSerializable {
        private readonly float $start;
        private readonly float $end;

        public function __construct(float $start, float $end) {
            $this->start = $start;
            $this->end = $end;
        }

        public function getStart() : float {
            return $this->start;
        }

        public function getEnd() : float {
            return $this->end;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>