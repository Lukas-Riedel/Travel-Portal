<?php
    namespace Core\Client\Forecast;

    use Common\Client\Http\HttpMethod;
    use Core\Client\Http\HttpClient;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Forecast\Weather;

    class YrNoActualForecastClient implements ForecastClient {

        private const GET_ACTUAL_WEATHER_FORECAST_ENDPOINT_FORMAT = "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=%s&lon=%s";
        
        private readonly HttpClient $httpClient;

        private ?ConfigurationService $configurationService;

        public function __construct(HttpClient $httpClient) {
            $this->httpClient = $httpClient;
        }

        public function setConfigurationService(ConfigurationService $configurationService) : void {
            $this->configurationService = $configurationService;
        }
        
        public function getForecast(float $latitude, float $longitude, int $start, int $end) : ?Weather {
            $apiResponse = $this->httpClient->executeRequest(HttpMethod::GET, sprintf(self::GET_ACTUAL_WEATHER_FORECAST_ENDPOINT_FORMAT,
                round($latitude, 4), round($longitude, 4)),
                array("User-Agent: " . BASE_URL . " " . $this->configurationService->getConfigurationEntry("contactDetails")["email"]), null, true);

            if (!isset($apiResponse["properties"]) || !isset($apiResponse["properties"]["timeseries"]) || $apiResponse["properties"]["timeseries"] == null) {
                throw new \RuntimeException("Unable to fetch the forecast. Response: " . json_encode($apiResponse));
            }

            $bestForecast = null;
            foreach ($apiResponse["properties"]["timeseries"] as &$forecast) {
                $forecastTime = strtotime($forecast["time"]);
                if ($forecastTime > $start) {
                    break;
                }
                $bestForecast = $forecast;
            }         

            if ($bestForecast === null || strtotime($bestForecast["time"]) + 6 * CommonConstants::ONE_HOUR_SECONDS < $start) {
                return null;
            }

            $convertedForecast = array(
                "temperature" => $bestForecast["data"]["instant"]["details"]["air_temperature"],
                "clouds" => $bestForecast["data"]["instant"]["details"]["cloud_area_fraction"],
                "wind" => $bestForecast["data"]["instant"]["details"]["wind_speed"],
                "symbol" => null,
                "precipitation" => 0,
                "updatedAt" => strtotime($apiResponse["properties"]["meta"]["updated_at"])
            );
            
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

            $expiration = isset($apiResponse["__httpHeaders"]["Expires"]) 
                ? strtotime($apiResponse["__httpHeaders"]["Expires"]) : time() + CommonConstants::ONE_HOUR_SECONDS;
            return new Weather($convertedForecast["temperature"], $convertedForecast["clouds"], $convertedForecast["wind"],
                $convertedForecast["precipitation"], $convertedForecast["symbol"], $convertedForecast["updatedAt"], $expiration);
        }
    }
?>