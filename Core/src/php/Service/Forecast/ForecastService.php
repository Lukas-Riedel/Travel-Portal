<?php
    namespace Core\Service\Forecast;
    
    use AurorasLive\SunCalc;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Place\PlaceIdentifier;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Common\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;

    class ForecastService {

        private const GET_HISTORICAL_WEATHER_FORECAST_ENDPOINT_FORMAT = "https://archive-api.open-meteo.com/v1/archive?latitude=%s&longitude=%s&start_date=%s&end_date=%s&daily=temperature_2m_max,precipitation_sum,windspeed_10m_max&timezone=%s&windspeed_unit=ms&timeformat=unixtime";
        private const GET_ACTUAL_WEATHER_FORECAST_ENDPOINT_FORMAT = "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=%s&lon=%s";
        private const HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER = 2;

        private readonly ForecastMapper $forecastMapper;

        private readonly HttpClient $httpClient;

        private readonly ConfigurationService $configurationService;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, HttpClient $httpClient, ConfigurationService $configurationService) {
            $this->forecastMapper = new ForecastMapper($databaseClient);
            $this->httpClient = $httpClient;
            $this->configurationService = $configurationService;
            $this->transactionManager = $databaseClient;
        }

        public function isActualWeatherForecastExpired(string $placeId, int $timestamp) : bool {
            $actualForecastExpiration = $this->forecastMapper->selectActualWeatherForecastExpiration($placeId, $timestamp);
            return $actualForecastExpiration === null || $actualForecastExpiration < time();
        }

        public function getWeatherForecast(string $placeId, int $timestamp) : ?Weather {
            $actualForecast = $this->forecastMapper->selectActualWeatherForecast($placeId, $timestamp);
            return $actualForecast !== null
                ? $actualForecast 
                : $this->forecastMapper->selectHistoricalWeatherForecast($placeId, $timestamp);
        }

        public function getDaylightForecast(string $placeId, int $timestamp) : ?Sun {
            return $this->forecastMapper->selectDaylightForecast($placeId, $timestamp);
        }

        public function updateDaylightForecast(PlaceIdentifier $placeIdentifier, int $start, int $end) : void {
            $dateTime = new \DateTime();
            $dateTime->setTimestamp($start);
            $suncalc = new SunCalc($dateTime, $placeIdentifier->getLatitude(), $placeIdentifier->getLongitude());
            $sunTimes = $suncalc->getSunTimes();
            $startSunPosition = $suncalc->getSunPosition($dateTime);
            $dateTime->setTimestamp($end);
            $endSunPosition = $suncalc->getSunPosition($dateTime);

            $daylightForecast = new Sun($sunTimes["sunrise"]->getTimestamp(), $sunTimes["sunset"]->getTimestamp(),
                $startSunPosition->altitude * 180 / M_PI, $endSunPosition->altitude * 180 / M_PI,
                $startSunPosition->azimuth * 180 / M_PI, $endSunPosition->azimuth * 180 / M_PI);
            
            $this->transactionManager->executeAtomically(function() use (&$placeIdentifier, &$daylightForecast, &$start) {
                $this->forecastMapper->deleteDaylightForecast($placeIdentifier->getId(), $start);
                $this->forecastMapper->insertDaylightForecast($daylightForecast, $placeIdentifier->getId(), $start);
            });

            $this->forecastMapper->deleteStaleDaylightForecast();
        }

        public function updateHistoricalWeatherForecast(PlaceIdentifier $placeIdentifier, int $timestamp) : void {
            $oneYearAgoTimestamp = $timestamp;
            while ($oneYearAgoTimestamp > time() - (1 + self::HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER) * CommonConstants::ONE_DAY_SECONDS) {
                $oneYearAgoTimestamp -= CommonConstants::ONE_YEAR_SECONDS;
            } 
    
            $startDate = date(CommonConstants::YMD_DATE_FORMAT, $oneYearAgoTimestamp - self::HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER * CommonConstants::ONE_DAY_SECONDS);
            $endDate = date(CommonConstants::YMD_DATE_FORMAT, $oneYearAgoTimestamp + self::HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER * CommonConstants::ONE_DAY_SECONDS);
        
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_HISTORICAL_WEATHER_FORECAST_ENDPOINT_FORMAT,
                $placeIdentifier->getLatitude(), $placeIdentifier->getLongitude(), $startDate, $endDate,
                $this->configurationService->getConfigurationEntry("homeLocation")["timezone"]));

            if (!isset($apiResponse["daily"])) {
                throw new \RuntimeException("Unable to fetch the forecast. Response: " . json_encode($apiResponse));
            }

            $temperature = $this->getAverage($apiResponse["daily"]["temperature_2m_max"]);
            $windspeed = $this->getAverage($apiResponse["daily"]["windspeed_10m_max"]);
            $precipitation = $this->getAverage($apiResponse["daily"]["precipitation_sum"]) / 24;

            $historicalForecast = new Weather($temperature, null, $windspeed, $precipitation, null, time());
    
            $this->transactionManager->executeAtomically(function() use (&$placeIdentifier, &$historicalForecast, &$timestamp) {
                $this->forecastMapper->deleteHistoricalWeatherForecast($placeIdentifier->getId(), $timestamp);
                $this->forecastMapper->insertHistoricalWeatherForecast($historicalForecast, $placeIdentifier->getId(), $timestamp);                
            });
            $this->forecastMapper->deleteStaleHistoricalWeatherForecast();
        }

        public function updateActualWeatherForecast(PlaceIdentifier $placeIdentifier, int $timestamp) : void {        
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_ACTUAL_WEATHER_FORECAST_ENDPOINT_FORMAT,
                round($placeIdentifier->getLatitude(), 4), round($placeIdentifier->getLongitude(), 4)),
                array("User-Agent: " . BASE_URL . " " . $this->configurationService->getConfigurationEntry("contactDetails")["email"]), null, true);

            if (!isset($apiResponse["properties"]) || !isset($apiResponse["properties"]["timeseries"]) || $apiResponse["properties"]["timeseries"] == null) {
                throw new \RuntimeException("Unable to fetch the forecast. Response: " . json_encode($apiResponse));
            }

            $bestForecast = null;
            foreach ($apiResponse["properties"]["timeseries"] as &$forecast) {
                $forecastTime = strtotime($forecast["time"]);
                if ($forecastTime > $timestamp) {
                    break;
                }
                $bestForecast = $forecast;
            }         

            if ($bestForecast === null || strtotime($bestForecast["time"]) + 6 * CommonConstants::ONE_HOUR_SECONDS < $timestamp) {
                return;
            }

            $convertedForecast = array(
                "temperature" => $bestForecast["data"]["instant"]["details"]["air_temperature"],
                "clouds" => $bestForecast["data"]["instant"]["details"]["cloud_area_fraction"],
                "wind" => $bestForecast["data"]["instant"]["details"]["wind_speed"],
                "symbol" => null,
                "precipitation" => 0,
                "updatedAt" => strtotime($apiResponse["properties"]["meta"]["updated_at"]));
            
            if (array_key_exists("next_1_hours", $bestForecast["data"])) {
                if (array_key_exists("summary", $bestForecast["data"]["next_1_hours"])) {
                    if (array_key_exists("symbol_code", $bestForecast["data"]["next_1_hours"]["summary"])) {
                        $convertedForecast["symbol"] = explode("_", $bestForecast["data"]["next_1_hours"]["summary"]["symbol_code"])[0];
                    }
                }
                if (array_key_exists("details", $bestForecast["data"]["next_1_hours"])) {
                    if (array_key_exists("precipitation_amount", $bestForecast["data"]["next_1_hours"]["details"])) {
                        $convertedForecast["precipitation"] = $bestForecast["data"]["next_1_hours"]["details"]["precipitation_amount"];
                    }
                }
            }                        
            else if (array_key_exists("next_6_hours", $bestForecast["data"])) {
                if (array_key_exists("summary", $bestForecast["data"]["next_6_hours"])) {
                    if (array_key_exists("symbol_code", $bestForecast["data"]["next_6_hours"]["summary"])) {
                        $convertedForecast["symbol"] = explode("_", $bestForecast["data"]["next_6_hours"]["summary"]["symbol_code"])[0];
                    }
                }
                if (array_key_exists("details", $bestForecast["data"]["next_6_hours"])) {
                    if (array_key_exists("precipitation_amount", $bestForecast["data"]["next_6_hours"]["details"])) {
                        $convertedForecast["precipitation"] = $bestForecast["data"]["next_6_hours"]["details"]["precipitation_amount"] / 6;
                    }
                }
            }

            $actualForecast = new Weather($convertedForecast["temperature"], $convertedForecast["clouds"], $convertedForecast["wind"],
                $convertedForecast["precipitation"], $convertedForecast["symbol"], $convertedForecast["updatedAt"]);
            $expiration = isset($apiResponse["__httpHeaders"]["Expires"]) 
                ? strtotime($apiResponse["__httpHeaders"]["Expires"]) : (time() + CommonConstants::ONE_HOUR_SECONDS);

            $this->transactionManager->executeAtomically(function() use (&$placeIdentifier, &$actualForecast, &$timestamp, &$expiration) {
                $this->forecastMapper->deleteActualWeatherForecast($placeIdentifier->getId(), $timestamp);
                $this->forecastMapper->insertActualWeatherForecast($actualForecast, $placeIdentifier->getId(), $timestamp, $expiration);
            });

            $this->forecastMapper->deleteStaleActualWeatherForecast();
        }
    
        private function getAverage(array $values) : ?float {
            return count($values) === 0 ? null : (array_sum($values) / count($values));
        }
    }
?>