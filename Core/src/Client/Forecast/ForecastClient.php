<?php
    namespace Core\Client\Forecast;

    use Core\Service\Forecast\Weather;

    interface ForecastClient {
        public function getForecast(float $latitude, float $longitude, int $start, int $end) : ?Weather;
    }
?>