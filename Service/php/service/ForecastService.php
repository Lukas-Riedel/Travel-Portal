<?php
    require_once(dirname(__FILE__) . "/../model/Weather.php");
    require_once(dirname(__FILE__) . "/../model/Sun.php");

    class ForecastService {
        public function getWeatherForecast($placeId, $timestamp) : ?Weather {
            global $databaseProvider;

            $actualForecastRow = $databaseProvider
                ->statementBuilder("SELECT * FROM forecast_actual WHERE place_id = ? AND timestamp = ?")
                ->withParameters($placeId, $timestamp)
                ->getSingleRow();

            if ($actualForecastRow !== NULL) {
                return new Weather($actualForecastRow["temperature"], $actualForecastRow["clouds"], $actualForecastRow["wind"],
                    $actualForecastRow["precipitation"], $actualForecastRow["symbol"], $actualForecastRow["last_update"]);
            }
            
            $historicalForecastRow = $databaseProvider
                ->statementBuilder("SELECT * FROM forecast_historical WHERE place_id = ? AND timestamp = ?")
                ->withParameters($placeId, $timestamp)
                ->getSingleRow();

            if ($historicalForecastRow !== NULL) {
                return new Weather($historicalForecastRow["temperature"], NULL, $historicalForecastRow["wind"],
                    $historicalForecastRow["precipitation"], NULL, time());
            }

            return NULL;
        }

        public function getSunForecast($placeId, $timestamp) : ?Sun {
            global $databaseProvider;

            $sunForecastRow = $databaseProvider
                ->statementBuilder("SELECT * FROM forecast_daylight WHERE place_id = ? AND timestamp = ?")
                ->withParameters($placeId, $timestamp)
                ->getSingleRow();

            if ($sunForecastRow !== NULL) {
                return new Sun($sunForecastRow["sunrise"], $sunForecastRow["sunset"], $sunForecastRow["start_sun_altitude"], 
                    $sunForecastRow["end_sun_altitude"], $sunForecastRow["start_sun_azimuth"], $sunForecastRow["end_sun_azimuth"]);
            }

            return NULL;
        }

        public function updateActualWeatherForecast($placeId, $timestamp, $latitude, $longitude) : void {
            global $databaseProvider, $configuration, $httpClient;
        
            $apiResponse = $httpClient->executeRequest("GET", "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=" . round($latitude, 4) . "&lon=" . round($longitude, 4),
                array("User-Agent: " . BASE_URL . " " . $configuration["contactEmail"]), NULL, TRUE);

            if (!isset($apiResponse["properties"]) || !isset($apiResponse["properties"]["timeseries"]) || $apiResponse["properties"]["timeseries"] == NULL) {
                throw new RuntimeException("Unable to fetch the forecast. Response: " . json_encode($apiResponse));
            }

            $bestForecast = NULL;
            foreach ($apiResponse["properties"]["timeseries"] as &$forecast) {
                $forecastTime = strtotime($forecast["time"]);
                if ($forecastTime > intval($timestamp)) {
                    break;
                }
                $bestForecast = $forecast;
            }         

            if ((strtotime($bestForecast["time"]) + 21600) < intval($timestamp)) {
                $bestForecast = NULL;
            }

            if ($bestForecast != NULL) {
                $convertedForecast = array();
                $convertedForecast["temperature"] = $bestForecast["data"]["instant"]["details"]["air_temperature"];
                $convertedForecast["clouds"] = $bestForecast["data"]["instant"]["details"]["cloud_area_fraction"];
                $convertedForecast["wind"] = $bestForecast["data"]["instant"]["details"]["wind_speed"];
                $convertedForecast["symbol"] = NULL;
                $convertedForecast["precipitation"] = 0;
                $convertedForecast["updatedAt"] = strtotime($apiResponse["properties"]["meta"]["updated_at"]);
                
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

                $databaseProvider
                    ->statementBuilder("DELETE FROM forecast_actual WHERE place_id = ? AND timestamp = ?")
                    ->withParameters($placeId, $timestamp)
                    ->execute();

                $databaseProvider
                    ->statementBuilder("INSERT INTO forecast_actual (place_id, timestamp, temperature, wind, precipitation, clouds, symbol, last_update, expiration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->withParameters($placeId, $timestamp, $convertedForecast["temperature"], $convertedForecast["wind"], $convertedForecast["precipitation"], $convertedForecast["clouds"], $convertedForecast["symbol"], $convertedForecast["updatedAt"], (isset($apiResponse["__httpHeaders"]["Expires"]) ? strtotime($apiResponse["__httpHeaders"]["Expires"]) : (time() + 3600)))
                    ->execute();
            }
        }
    }
?>