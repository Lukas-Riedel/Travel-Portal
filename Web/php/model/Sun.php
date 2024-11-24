<?php
    require_once(dirname(__FILE__) . "/Interval.php");

    class Sun implements JsonSerializable {
        private $sunrise;
        private $sunset;
        private $altitude;
        private $azimuth;

        public function __construct($sunrise, $sunset, $startAltitude, $endAltitude, $startAzimuth, $endAzimuth) {
            $this->sunrise = $sunrise;
            $this->sunset = $sunset;
            $this->altitude = new Interval($startAltitude, $endAltitude);
            $this->azimuth = new Interval($startAzimuth, $endAzimuth);
        }

        public function getSunrise() : int {
            return $this->sunrise;
        }

        public function getSunset() : int {
            return $this->sunset;
        }

        public function getAltitude() : Interval {
            return $this->altitude;
        }

        public function getAzimuth() : Interval {
            return $this->azimuth;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>