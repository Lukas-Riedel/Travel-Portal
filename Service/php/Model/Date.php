<?php
    require_once(dirname(__FILE__) . "/Weather.php");
    require_once(dirname(__FILE__) . "/Sun.php");
    require_once(dirname(__FILE__) . "/Album.php");
    require_once(dirname(__FILE__) . "/TripIdentifier.php");

    class Date implements JsonSerializable {        
        private $start;
        private $end;
        private $layover;
        private $weather;
        private $sun;
        private $album;
        private $trip;

        public function __construct($start, $end, $layover, $weather, $sun, $album, $trip) {
            $this->start = $start;
            $this->end = $end;
            $this->layover = $layover;
            $this->weather = $weather;
            $this->sun = $sun;
            $this->album = $album;
            $this->trip = $trip;
        }

        public function getStart() : int {
            return $this->start;
        }

        public function getEnd() : int {
            return $this->end;
        }

        public function isLayover() : bool {
            return $this->layover;
        }

        public function getWeather() : ?Weather {
            return $this->weather;
        }

        public function getSun() : ?Sun {
            return $this->sun;
        }

        public function getAlbum() : ?Album {
            return $this->album;
        }

        public function getTrip() : ?TripIdentifier {
            return $this->trip;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>