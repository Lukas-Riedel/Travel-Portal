<?php
    namespace Core\Client\Forecast;

    interface ForecastClient {
        public function getForecast(float $latitude, float $longitude, int $start, int $end) : ?Weather;
    }
?>