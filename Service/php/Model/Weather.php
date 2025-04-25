<?php
    class Weather implements JsonSerializable {        
        private $temperature;
        private $clouds;
        private $wind;
        private $precipitation;
        private $symbol;
        private $lastUpdate;

        public function __construct($temperature, $clouds, $wind, $precipitation, $symbol, $lastUpdate) {
            $this->temperature = $temperature;
            $this->clouds = $clouds;
            $this->wind = $wind;
            $this->precipitation = $precipitation;
            $this->symbol = $symbol;
            $this->lastUpdate = $lastUpdate;
        }

        public function getTemperature() : float {
            return $this->temperature;
        }

        public function getClouds() : float {
            return $this->clouds;
        }

        public function getWind() : float {
            return $this->wind;
        }

        public function getPrecipitation() : float {
            return $this->precipitation;
        }

        public function getSymbol() : string {
            return $this->symbol;
        }

        public function getLastUpdate() : int {
            return $this->lastUpdate;
        }

        #[\ReturnTypeWillChange]
        public function jsonSerialize() : mixed {
            return get_object_vars($this);
        }
    }
?>