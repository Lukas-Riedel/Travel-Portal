<?php
    class TripVacation implements JsonSerializable {
        private $expected;
        private $maximum;

        public function __construct($expected, $maximum) {
            $this->expected = max(0, $expected);
            $this->maximum = max(0, $maximum);
        }

        public function getExpected() : float {
            return $this->expected;
        }

        public function getMaximum() : float {
            return $this->maximum;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>