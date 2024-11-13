<?php
    class Weather implements JsonSerializable {        
        private $temperature;
        private $clouds;
        private $wind;
        private $precipitation;
        private $symbol;
        private $sunrise;
        private $sunset;
        private $sunAltitude;
        private $sunAzimuth;
        private $lastUpdate;

        public function __construct($temperature, $clouds, $wind, $precipitation, $symbol, $sunrise, $sunset, $startSunAltitude, $endSunAltitude, $startSunAzimuth, $endSunAzimuth, $lastUpdate) {
            $this->temperature = $temperature;
            $this->clouds = $clouds;
            $this->wind = $wind;
            $this->precipitation = $precipitation;
            $this->symbol = $symbol;
            $this->sunrise = $sunrise;
            $this->sunset = $sunset;
            $this->sunAltitude = array("start" => $startSunAltitude, "end" => $endSunAltitude);
            $this->sunAzimuth = array("start" => $startSunAzimuth, "end" => $endSunAzimuth);
            $this->lastUpdate = $lastUpdate;
        }

        public function getTemperature() {
            return $this->temperature;
        }

        public function getClouds() {
            return $this->clouds;
        }

        public function getWind() {
            return $this->wind;
        }

        public function getPrecipitation() {
            return $this->precipitation;
        }

        public function getSymbol() {
            return $this->symbol;
        }

        public function getSunrise() {
            return $this->sunrise;
        }

        public function getSunset() {
            return $this->sunset;
        }

        public function getSunAltitude() {
            return $this->sunAltitude;
        }

        public function getSunAzimuth() {
            return $this->sunAzimuth;
        }

        public function getLastUpdate() {
            return $this->lastUpdate;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>