<?php
    namespace Core\Client\Forecast;

    use Common\Client\Http\HttpMethod;
    use Common\Client\Http\HttpClient;
    use Core\Common\CommonConstants;
    use Core\Service\Forecast\Precipitation;
    use Core\Service\Forecast\Weather;

    class OpenMeteoHistoricalForecastClient implements ForecastClient {

        private const TEMPERATURE_VARIABLE_KEY = "temperature_2m_max";
        private const PRECIPITATION_VARIABLE_KEY = "precipitation_sum";
        private const WINDSPEED_VARIABLE_KEY = "windspeed_10m_max";
        
        private const GET_HISTORICAL_WEATHER_FORECAST_ENDPOINT_FORMAT = "https://archive-api.open-meteo.com/v1/archive?latitude=%f&longitude=%f&start_date=%s&end_date=%s&daily=%s&timezone=%s&windspeed_unit=ms&timeformat=unixtime";

        private readonly HttpClient $httpClient;

        public function __construct(HttpClient $httpClient) {
            $this->httpClient = $httpClient;
        }

        public function getForecast(float $latitude, float $longitude, int $start, int $end) : ?Weather {
            $startDate = date(CommonConstants::YMD_DATE_FORMAT, $start);
            $endDate = date(CommonConstants::YMD_DATE_FORMAT, $end);
        
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_HISTORICAL_WEATHER_FORECAST_ENDPOINT_FORMAT,
                $latitude, $longitude, $startDate, $endDate, implode(",", array(self::TEMPERATURE_VARIABLE_KEY, self::PRECIPITATION_VARIABLE_KEY, self::WINDSPEED_VARIABLE_KEY)), date_default_timezone_get()));

            if (!isset($apiResponse["daily"])) {
                throw new \RuntimeException("Unable to fetch historical forecast. Response: " . json_encode($apiResponse));
            }

            $temperature = $this->getAverage($apiResponse["daily"][self::TEMPERATURE_VARIABLE_KEY]);
            $windspeed = $this->getAverage($apiResponse["daily"][self::WINDSPEED_VARIABLE_KEY]);
            $precipitation = $this->getAverage($apiResponse["daily"][self::PRECIPITATION_VARIABLE_KEY]) / 24;

            return new Weather($temperature, null, $windspeed, new Precipitation($precipitation, null), null, time(), $end + CommonConstants::ONE_YEAR_SECONDS);
        }
    
        private function getAverage(array $values) : ?float {
            return count($values) === 0 ? null : (array_sum($values) / count($values));
        }
    }
?>