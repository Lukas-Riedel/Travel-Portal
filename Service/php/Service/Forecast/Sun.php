<?php
    namespace Service\Service\Forecast;

    class Sun implements \JsonSerializable {
        private readonly int $sunrise;
        private readonly int $sunset;
        private readonly Interval $altitude;
        private readonly Interval $azimuth;

        public function __construct(int $sunrise, int $sunset, float $startAltitude,
            float $endAltitude, float $startAzimuth, float $endAzimuth) {
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