<?php
    class Sun implements JsonSerializable {
        private $sunrise;
        private $sunset;
        private $altitude;
        private $azimuth;

        public function __construct($sunrise, $sunset, $startAltitude, $endAltitude, $startAzimuth, $endAzimuth) {
            $this->sunrise = $sunrise;
            $this->sunset = $sunset;
            $this->altitude = array("start" => $startAltitude, "end" => $endAltitude);
            $this->azimuth = array("start" => $startAzimuth, "end" => $endAzimuth);
        }

        public function getSunrise() {
            return $this->sunrise;
        }

        public function getSunset() {
            return $this->sunset;
        }

        public function getaltitude() {
            return $this->altitude;
        }

        public function getazimuth() {
            return $this->azimuth;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>