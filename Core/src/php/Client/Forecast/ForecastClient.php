<?php
    namespace Core\Client\Forecast;

    use Core\Service\Forecast\Weather;

    interface ForecastClient {
        public function fetchForecast(float $latitude, float $longitude, int $start, int $end) : ?Weather;
    }
?>