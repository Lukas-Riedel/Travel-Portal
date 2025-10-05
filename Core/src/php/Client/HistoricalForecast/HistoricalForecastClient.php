<?php
    namespace Core\Client\HistoricalForecast;

    use Core\Service\Forecast\Weather;

    interface HistoricalForecastClient {
        public function fetchForecast(float $latitude, float $longitude, int $start, int $end) : Weather;
    }
?>