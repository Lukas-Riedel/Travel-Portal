<?php
    namespace Core\Client\Forecast;

    use Common\Client\Cache\CacheClient;
    use Common\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;
    use Core\Common\CommonConstants;
    use Core\Service\Forecast\Clouds;
    use Core\Service\Forecast\Precipitation;
    use Core\Service\Forecast\Weather;

    class OpenMeteoActualForecastClient implements ForecastClient {

        private const ENSEMBLE_API_RESPONSE_CACHE_KEY_FORMAT = "OpenMeteoActualForecastClient:EnsembleApiResponse:%f:%f";
        private const ENSEMBLE_API_RESPONSE_CACHE_TTL = 900;

        private const TEMPERATURE_VARIABLE_KEY = "temperature_2m";
        private const PRECIPITATION_VARIABLE_KEY = "precipitation";
        private const WINDSPEED_VARIABLE_KEY = "wind_speed_10m";
        private const HUMIDITY_VARIABLE_KEY = "relative_humidity_2m";
        private const CLOUD_COVER_VARIABLE_KEY = "cloud_cover";
        private const CLOUD_COVER_LOW_VARIABLE_KEY = "cloud_cover_low";
        private const CLOUD_COVER_MID_VARIABLE_KEY = "cloud_cover_mid";
        private const CLOUD_COVER_HIGH_VARIABLE_KEY = "cloud_cover_high";

        private const VARIABLE_KEY_PREFIX_SUFFIX = "_member";
        
        private const GET_ENSEMBLE_WEATHER_FORECAST_ENDPOINT_FORMAT = "https://ensemble-api.open-meteo.com/v1/ensemble?latitude=%f&longitude=%f&hourly=%s&models=%s&timezone=%s&forecast_days=%d&wind_speed_unit=ms&timeformat=unixtime";

        private readonly HttpClient $httpClient;
        private readonly CacheClient $distributedCacheClient;

        private readonly array $models;
        private readonly int $actualWeatherForecastDaysToCache;

        public function __construct(HttpClient $httpClient, CacheClient $distributedCacheClient, array $models, int $actualWeatherForecastDaysToCache) {
            $this->httpClient = $httpClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->models = $models;
            $this->actualWeatherForecastDaysToCache = $actualWeatherForecastDaysToCache;
        }

        public function getForecast(float $latitude, float $longitude, int $start, int $end) : Weather {
            $cacheKey = sprintf(self::ENSEMBLE_API_RESPONSE_CACHE_KEY_FORMAT, $latitude, $longitude);
            $apiResponse = $this->distributedCacheClient->get($cacheKey);

            if ($apiResponse === null) {
                $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_ENSEMBLE_WEATHER_FORECAST_ENDPOINT_FORMAT,
                    $latitude, $longitude, implode(",", array(self::TEMPERATURE_VARIABLE_KEY, self::PRECIPITATION_VARIABLE_KEY, self::WINDSPEED_VARIABLE_KEY, self::CLOUD_COVER_VARIABLE_KEY,
                    self::CLOUD_COVER_LOW_VARIABLE_KEY, self::CLOUD_COVER_MID_VARIABLE_KEY, self::CLOUD_COVER_HIGH_VARIABLE_KEY, self::HUMIDITY_VARIABLE_KEY)), implode(",", $this->models),
                    date_default_timezone_get(), $this->actualWeatherForecastDaysToCache + 1));
                $this->distributedCacheClient->set($cacheKey, $apiResponse, self::ENSEMBLE_API_RESPONSE_CACHE_TTL);
            }

            if (!isset($apiResponse["hourly"])) {
                throw new \RuntimeException("Unable to fetch actual forecast. Response: " . json_encode($apiResponse));
            }

            $index = -1;
            foreach ($apiResponse["hourly"]["time"] as $k => $time) {
                if ($time >= $start) {
                    $index = $k;
                    break;
                }
            }

            if ($index === -1) {
                throw new \RuntimeException("Unable to fetch actual forecast. No forecast data available for the given time range. Response: " . json_encode($apiResponse));
            }
            
            $temperatureValues = $this->extractValues($apiResponse, $index, self::TEMPERATURE_VARIABLE_KEY);
            $precipitationValues = $this->extractValues($apiResponse, $index, self::PRECIPITATION_VARIABLE_KEY);
            $windspeedValues = $this->extractValues($apiResponse, $index, self::WINDSPEED_VARIABLE_KEY);
            $humidityValues = $this->extractValues($apiResponse, $index, self::HUMIDITY_VARIABLE_KEY);
            $cloudCoverValues = $this->extractValues($apiResponse, $index, self::CLOUD_COVER_VARIABLE_KEY);
            $cloudCoverLowValues = $this->extractValues($apiResponse, $index, self::CLOUD_COVER_LOW_VARIABLE_KEY);
            $cloudCoverMidValues = $this->extractValues($apiResponse, $index, self::CLOUD_COVER_MID_VARIABLE_KEY);
            $cloudCoverHighValues = $this->extractValues($apiResponse, $index, self::CLOUD_COVER_HIGH_VARIABLE_KEY);

            return new Weather(
                $this->getAverage($temperatureValues), 
                new Clouds(
                    $this->getAverage($cloudCoverValues), 
                    $this->getAverage($cloudCoverLowValues), 
                    $this->getAverage($cloudCoverMidValues), 
                    $this->getAverage($cloudCoverHighValues)
                ),
                $this->getAverage($windspeedValues),
                new Precipitation(
                    $this->getMedian($precipitationValues),
                    $this->getPrecipitationProbability($precipitationValues)
                ),
                $this->getAverage($humidityValues),
                time(), 
                time() + CommonConstants::ONE_HOUR_SECONDS
            );
        }

        private function extractValues(mixed $apiResponse, int $index, string $variableKey) : array {
            $variableKeyPrefix = $variableKey . self::VARIABLE_KEY_PREFIX_SUFFIX;

            $result = array();
            foreach ($apiResponse["hourly"] as $key => $values) {
                if (str_starts_with($key, $variableKeyPrefix)) {
                    if ($values[$index] !== null) {
                        $result[] = $values[$index];
                    }
                }
            }
            return $result;
        }  

        private function getAverage(array $values) : ?float {
            return count($values) === 0 ? null : (array_sum($values) / count($values));
        }

        private function getMedian(array $values): ?float {
            $count = count($values);
            if ($count === 0) {
                return null;
            }

            $sortedValues = $values;
            sort($sortedValues);
            $mid = (int)($count / 2);

            return ($count % 2 === 0) ? ($sortedValues[$mid - 1] + $sortedValues[$mid]) / 2 : $sortedValues[$mid];
        }

        private function getPrecipitationProbability(array $values) : float {
            return count($values) === 0 ? 0 : round((count(array_filter($values, fn($v) => $v >= 0.1)) / count($values)) * 100);
        }
    }
?>