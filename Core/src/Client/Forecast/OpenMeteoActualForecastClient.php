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

        private const ENSEMBLE_API_RESPONSE_CACHE_KEY_FORMAT = "OpenMeteoActualForecastClient:EnsembleApiResponse:%s:%s:%f:%f:";

        private const TEMPERATURE_VARIABLE_KEY = "temperature_2m";
        private const PRECIPITATION_VARIABLE_KEY = "precipitation";
        private const WINDSPEED_VARIABLE_KEY = "wind_speed_10m";
        private const HUMIDITY_VARIABLE_KEY = "relative_humidity_2m";
        private const CLOUD_COVER_VARIABLE_KEY = "cloud_cover";
        private const CLOUD_COVER_LOW_VARIABLE_KEY = "cloud_cover_low";
        private const CLOUD_COVER_MID_VARIABLE_KEY = "cloud_cover_mid";
        private const CLOUD_COVER_HIGH_VARIABLE_KEY = "cloud_cover_high";

        private const VARIABLE_KEY_PREFIX_SUFFIX = "_member";
        
        private const GET_ENSEMBLE_WEATHER_FORECAST_ENDPOINT_FORMAT = "https://ensemble-api.open-meteo.com/v1/ensemble?latitude=%f&longitude=%f&hourly=%s&models=%s&timezone=%s&start_date=%s&end_date=%s&wind_speed_unit=ms&timeformat=unixtime";

        private readonly HttpClient $httpClient;
        private readonly CacheClient $distributedCacheClient;

        private readonly array $models;
        private readonly array $refreshHours;

        public function __construct(HttpClient $httpClient, CacheClient $distributedCacheClient, array $models, array $refreshHours) {
            $this->httpClient = $httpClient;
            $this->distributedCacheClient = $distributedCacheClient;
            $this->models = $models;
            $this->refreshHours = $refreshHours;
        }

        public function getForecast(float $latitude, float $longitude, int $start, int $end) : Weather {
            $startDate = date(CommonConstants::YMD_DATE_FORMAT, $start);
            $endDate = date(CommonConstants::YMD_DATE_FORMAT, $end);

            $cacheKey = sprintf(self::ENSEMBLE_API_RESPONSE_CACHE_KEY_FORMAT, $startDate, $endDate, round($latitude, 2), round($longitude, 2));
            $apiResponse = $this->distributedCacheClient->get($cacheKey);

            $expiration = $this->getExpirationTimestamp();
            if ($apiResponse === null) {
                $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_ENSEMBLE_WEATHER_FORECAST_ENDPOINT_FORMAT,
                    round($latitude, 2), round($longitude, 2), implode(",", array(self::TEMPERATURE_VARIABLE_KEY, self::PRECIPITATION_VARIABLE_KEY, self::WINDSPEED_VARIABLE_KEY, self::CLOUD_COVER_VARIABLE_KEY,
                    self::CLOUD_COVER_LOW_VARIABLE_KEY, self::CLOUD_COVER_MID_VARIABLE_KEY, self::CLOUD_COVER_HIGH_VARIABLE_KEY, self::HUMIDITY_VARIABLE_KEY)), implode(",", $this->models),
                    date_default_timezone_get(), $startDate, $endDate));

                if (!isset($apiResponse["hourly"])) {
                    throw new \RuntimeException("Unable to fetch actual forecast. Response: " . json_encode($apiResponse));
                }
                
                $this->distributedCacheClient->set($cacheKey, $apiResponse, max(0, $expiration - time()));
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
                $this->getMedian($temperatureValues), 
                new Clouds(
                    $this->getMedian($cloudCoverValues), 
                    $this->getMedian($cloudCoverLowValues), 
                    $this->getMedian($cloudCoverMidValues), 
                    $this->getMedian($cloudCoverHighValues),
                    $this->getConfidence($cloudCoverValues)
                ),
                $this->getMedian($windspeedValues),
                new Precipitation(
                    $this->getMedian($precipitationValues),
                    $this->getPrecipitationProbability($precipitationValues)
                ),
                $this->getMedian($humidityValues),
                time(), 
                $expiration
            );
        }

        private function getExpirationTimestamp() : int {
            $now = time();
            $startOfTodayUtc = $now - ($now % CommonConstants::ONE_DAY_SECONDS);

            foreach ($this->refreshHours as $hour) {
                $refreshTimestamp = $startOfTodayUtc + ($hour * CommonConstants::ONE_HOUR_SECONDS);
                
                if ($refreshTimestamp > $now) {
                    return $refreshTimestamp;
                }
            }

            $firstRefreshHourTomorrow = $this->refreshHours[0];
            return $startOfTodayUtc + CommonConstants::ONE_DAY_SECONDS + ($firstRefreshHourTomorrow * CommonConstants::ONE_HOUR_SECONDS);
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

        private function getMedian(array $values) : ?float {
            $count = count($values);
            if ($count === 0) {
                return null;
            }

            $sortedValues = $values;
            sort($sortedValues);
            $mid = (int)($count / 2);

            return ($count % 2 === 0) ? ($sortedValues[$mid - 1] + $sortedValues[$mid]) / 2 : $sortedValues[$mid];
        }

        private function getConfidence(array $values) : int {
            $count = count($values);
            if ($count <= 1) {
                return 100;
            }

            $median = $this->getMedian($values);
            $tolerance = $this->getAdaptiveTolerance($values, $median);

            $agreeCount = count(array_filter($values, fn($v) => abs($v - $median) <= $tolerance));
            return (int) round(($agreeCount / $count) * 100);
        }

        private function getAdaptiveTolerance(array $values, float $median) : float {
            if ($median === null) {
                return 1.0;
            }
            
            $count = count($values);

            $sorted = $values;
            sort($sorted);

            $q1 = $sorted[(int)($count * 0.25)];
            $q3 = $sorted[(int)($count * 0.75)];
            $iqr = $q3 - $q1;

            if ($iqr === 0) {
                return max(1.0, abs($median) * 0.05);
            }

            return max(1.0, $iqr * 0.5);
        }

        private function getPrecipitationProbability(array $values) : float {
            return count($values) === 0 ? 0 : round((count(array_filter($values, fn($v) => $v > 0)) / count($values)) * 100);
        }
    }
?>