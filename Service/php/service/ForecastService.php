<?php
    require_once(dirname(__FILE__) . "/ForecastMapper.php");
    require_once(dirname(__FILE__) . "/../model/Weather.php");
    require_once(dirname(__FILE__) . "/../model/Sun.php");
    
    use AurorasLive\SunCalc;

    class ForecastService {

        private const FETCH_ACTUAL_WEATHER_FORECAST_ACTION_NAME = "FETCH_ACTUAL_WEATHER_FORECAST";
        private const FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_NAME = "FETCH_HISTORICAL_WEATHER_FORECAST";
        private const FETCH_DAYLIGHT_FORECAST_ACTION_NAME = "FETCH_DAYLIGHT_FORECAST";
        private const FETCH_ACTUAL_WEATHER_FORECAST_ACTION_INTERVAL = 300;
        private const FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_INTERVAL = 300;
        private const FETCH_DAYLIGHT_FORECAST_ACTION_INTERVAL = 300;
        private const GET_HISTORICAL_WEATHER_FORECAST_ENDPOINT_FORMAT = "https://archive-api.open-meteo.com/v1/archive?latitude=%s&longitude=%s&start_date=%s&end_date=%s&daily=temperature_2m_max,precipitation_sum,windspeed_10m_max&timezone=%s&windspeed_unit=ms&timeformat=unixtime";
        private const GET_ACTUAL_WEATHER_FORECAST_ENDPOINT_FORMAT = "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=%s&lon=%s";
        private const HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER = 2;
        private const ACTUAL_WEATHER_FORECAST_DAYS_TO_CACHE = 9;
        private const YMD_DATE_FORMAT = "Y-m-d";

        private readonly ForecastMapper $forecastMapper;

        private readonly HttpClient $httpClient;

        private readonly ConfigurationService $configurationService;

        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        public function __construct(DatabaseProvider $databaseProvider, HttpClient $httpClient, ConfigurationService $configurationService,
            EventPublisher $eventPublisher, Scheduler $scheduler) {
            $this->forecastMapper = new ForecastMapper($databaseProvider);
            $this->httpClient = $httpClient;
            $this->configurationService = $configurationService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function getWeatherForecast(string $placeId, int $timestamp) : ?Weather {
            $actualForecast = $this->forecastMapper->selectActualWeatherForecast($placeId, $timestamp);
            return $actualForecast !== NULL
                ? $actualForecast 
                : $this->forecastMapper->selectHistoricalWeatherForecast($placeId, $timestamp);
        }

        public function getDaylightForecast(string $placeId, int $timestamp) : ?Sun {
            return $this->forecastMapper->selectDaylightForecast($placeId, $timestamp);
        }

        public function updateDaylightForecast(PlaceIdentifier $placeIdentifier, int $start, int $end) : void {
            $dateTime = new DateTime();
            $dateTime->setTimestamp($start);
            $suncalc = new SunCalc($dateTime, $placeIdentifier->getLatitude(), $placeIdentifier->getLongitude());
            $sunTimes = $suncalc->getSunTimes();
            $startSunPosition = $suncalc->getSunPosition($dateTime);
            $dateTime->setTimestamp($end);
            $endSunPosition = $suncalc->getSunPosition($dateTime);

            $daylightForecast = new Sun($sunTimes["sunrise"]->getTimestamp(), $sunTimes["sunset"]->getTimestamp(),
                $startSunPosition->altitude * 180 / M_PI, $endSunPosition->altitude * 180 / M_PI,
                $startSunPosition->azimuth * 180 / M_PI, $endSunPosition->azimuth * 180 / M_PI);
            
            $this->forecastMapper->deleteDaylightForecast($placeIdentifier->getId(), $start);
            $this->forecastMapper->insertDaylightForecast($daylightForecast, $placeIdentifier->getId(), $start);
        }

        public function updateHistoricalWeatherForecast(PlaceIdentifier $placeIdentifier, int $timestamp) : void {
            $oneYearAgoTimestamp = $timestamp;
            while ($oneYearAgoTimestamp > time() - (1 + self::HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER) * 86400) {
                $oneYearAgoTimestamp -= 86400 * 365;
            } 
    
            $startDate = date(self::YMD_DATE_FORMAT, $oneYearAgoTimestamp - self::HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER * 86400);
            $endDate = date(self::YMD_DATE_FORMAT, $oneYearAgoTimestamp + self::HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER * 86400);
        
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_HISTORICAL_WEATHER_FORECAST_ENDPOINT_FORMAT,
                $placeIdentifier->getLatitude(), $placeIdentifier->getLongitude(), $startDate, $endDate,
                $this->configurationService->getConfigurationForTypeAndKey("homeLocation", "timezone")));

            if (!isset($apiResponse["daily"])) {
                throw new RuntimeException("Unable to fetch the forecast. Response: " . json_encode($apiResponse));
            }

            $temperature = $this->getAverage($apiResponse["daily"]["temperature_2m_max"]);
            $windspeed = $this->getAverage($apiResponse["daily"]["windspeed_10m_max"]);
            $precipitation = $this->getAverage($apiResponse["daily"]["precipitation_sum"]) / 24;

            $historicalForecast = new Weather($temperature, NULL, $windspeed, $precipitation, NULL, time());
    
            $this->forecastMapper->deleteHistoricalWeatherForecast($placeIdentifier->getId(), $timestamp);
            $this->forecastMapper->insertHistoricalWeatherForecast($historicalForecast, $placeIdentifier->getId(), $timestamp);
        }

        public function updateActualWeatherForecast(PlaceIdentifier $placeIdentifier, int $timestamp) : void {        
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_ACTUAL_WEATHER_FORECAST_ENDPOINT_FORMAT,
                round($placeIdentifier->getLatitude(), 4), round($placeIdentifier->getLongitude(), 4)),
                array("User-Agent: " . BASE_URL . " " . $this->configurationService->getConfigurationForType("contactEmail")), NULL, TRUE);

            if (!isset($apiResponse["properties"]) || !isset($apiResponse["properties"]["timeseries"]) || $apiResponse["properties"]["timeseries"] == NULL) {
                throw new RuntimeException("Unable to fetch the forecast. Response: " . json_encode($apiResponse));
            }

            $bestForecast = NULL;
            foreach ($apiResponse["properties"]["timeseries"] as &$forecast) {
                $forecastTime = strtotime($forecast["time"]);
                if ($forecastTime > $timestamp) {
                    break;
                }
                $bestForecast = $forecast;
            }         

            if ($bestForecast === NULL || strtotime($bestForecast["time"]) + 21600 < $timestamp) {
                return;
            }

            $convertedForecast = array(
                "temperature" => $bestForecast["data"]["instant"]["details"]["air_temperature"],
                "clouds" => $bestForecast["data"]["instant"]["details"]["cloud_area_fraction"],
                "wind" => $bestForecast["data"]["instant"]["details"]["wind_speed"],
                "symbol" => NULL,
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
            $expiration = isset($apiResponse["__httpHeaders"]["Expires"]) ? strtotime($apiResponse["__httpHeaders"]["Expires"]) : (time() + 3600);

            $this->forecastMapper->deleteActualWeatherForecast($placeIdentifier->getId(), $timestamp);
            $this->forecastMapper->insertActualWeatherForecast($actualForecast, $placeIdentifier->getId(), $timestamp, $expiration);
        }
    
        private function getAverage(array $values) : ?float {
            return count($values) === 0 ? NULL : (array_sum($values) / count($values));
        }

        public function onActualWeatherForecastUpdated($message) : void {
            // TODO: Introduce the PlaceService $placeService field after moving this method to a new listener class.
            global $placeService;

            $placeIdentifier = $placeService->getPlaceIdentifierById($message["placeId"]);
            $this->updateActualWeatherForecast($placeIdentifier, $message["start"]);
        }

        public function onHistoricalWeatherForecastUpdated($message) : void {
            // TODO: Introduce the PlaceService $placeService field after moving this method to a new listener class.
            global $placeService;

            $placeIdentifier = $placeService->getPlaceIdentifierById($message["placeId"]);
            $this->updateHistoricalWeatherForecast($placeIdentifier, $message["start"]);
        }

        public function onDaylightForecastUpdated($message) : void {
            // TODO: Introduce the PlaceService $placeService field after moving this method to a new listener class.
            global $placeService;

            $placeIdentifier = $placeService->getPlaceIdentifierById($message["placeId"]);
            $this->updateDaylightForecast($placeIdentifier, $message["start"], $message["end"]);
        }

        public function onSchedulerTriggered($message) : void {
            // TODO: Introduce the PlaceService $placeService field after moving this method to a new listener class.
            global $placeService;

            if ($message["action"] === self::FETCH_ACTUAL_WEATHER_FORECAST_ACTION_NAME
                && $message["timeSinceLastExecution"] > self::FETCH_ACTUAL_WEATHER_FORECAST_ACTION_INTERVAL) {
                $places = $placeService->getRegularPlaces(NULL, NULL, NULL, NULL, time(),
                    time() + self::ACTUAL_WEATHER_FORECAST_DAYS_TO_CACHE * 86400, array());

                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {
                        $forecastExpiration = $this->forecastMapper->selectActualWeatherForecastExpiration($place->getId(), $date->getStart());

                        if ($forecastExpiration < time()) {
                            $this->eventPublisher->publishActualWeatherForecastUpdated($place->getId(), $date->getStart());
                        }
                    }
                }
                
                $this->scheduler->recordEventsTriggered(self::FETCH_ACTUAL_WEATHER_FORECAST_ACTION_NAME);
            }

            if ($message["action"] === self::FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_NAME
                && $message["timeSinceLastExecution"] > self::FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_INTERVAL) {
                    $places = $placeService->getRegularPlaces(NULL, NULL, NULL, NULL, time(), NULL, array());
    
                    foreach ($places as &$place) {
                        foreach ($place->getDates() as &$date) {    
                            if ($date->getWeather() === NULL) {
                                $this->eventPublisher->publishHistoricalWeatherForecastUpdated($place->getId(), $date->getStart());
                            }
                        }
                    }
                
                $this->scheduler->recordEventsTriggered(self::FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_NAME);
            }

            if ($message["action"] === self::FETCH_DAYLIGHT_FORECAST_ACTION_NAME
                && $message["timeSinceLastExecution"] > self::FETCH_DAYLIGHT_FORECAST_ACTION_INTERVAL) {
                $places = $placeService->getRegularPlaces(NULL, NULL, NULL, NULL, time(), NULL, array());

                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {    
                        if ($date->getSun() === NULL) {
                            $this->eventPublisher->publishDaylightForecastUpdated($place->getId(), $date->getStart(), $date->getEnd());
                        }
                    }
                }
                
                $this->scheduler->recordEventsTriggered(self::FETCH_DAYLIGHT_FORECAST_ACTION_NAME);
            }
        }
    }
?>