<?php
    class Date implements JsonSerializable {        
        private $start;
        private $end;
        private $weather;
        private $album;
        private $trip;

        public function __construct($start, $end, $weather, $album, $trip) {
            $this->start = $start;
            $this->end = $end;
            $this->weather = $weather;
            $this->album = $album;
            $this->trip = $trip;
        }

        public function getStart() {
            return $this->start;
        }

        public function getEnd() {
            return $this->end;
        }

        public function getWeather() {
            return $this->weather;
        }

        public function getAlbum() {
            return $this->album;
        }

        public function getTrip() {
            return $this->trip;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() {
            return get_object_vars($this);
        }
    }
?>