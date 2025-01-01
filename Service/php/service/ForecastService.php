<?php
    require_once(dirname(__FILE__) . "/../model/Weather.php");
    require_once(dirname(__FILE__) . "/../model/Sun.php");
    require_once(dirname(__FILE__) . "/../lib/suncalc.php");

    class ForecastService {
        public function getWeatherForecast($placeId, $start) : ?Weather {
            global $databaseProvider;

            $actualForecastRow = $databaseProvider
                ->statementBuilder("SELECT * FROM forecast_actual WHERE place_id = ? AND timestamp = ?")
                ->withParameters($placeId, $start)
                ->getSingleRow();

            if ($actualForecastRow !== NULL) {
                return new Weather($actualForecastRow["temperature"], $actualForecastRow["clouds"], $actualForecastRow["wind"],
                    $actualForecastRow["precipitation"], $actualForecastRow["symbol"], $actualForecastRow["last_update"]);
            }
            
            $historicalForecastRow = $databaseProvider
                ->statementBuilder("SELECT * FROM forecast_historical WHERE place_id = ? AND timestamp = ?")
                ->withParameters($placeId, $start)
                ->getSingleRow();

            if ($historicalForecastRow !== NULL) {
                return new Weather($historicalForecastRow["temperature"], NULL, $historicalForecastRow["wind"],
                    $historicalForecastRow["precipitation"], NULL, time());
            }

            return NULL;
        }

        public function getSunForecast($placeId, $start) : ?Sun {
            global $databaseProvider;

            $sunForecastRow = $databaseProvider
                ->statementBuilder("SELECT * FROM forecast_daylight WHERE place_id = ? AND timestamp = ?")
                ->withParameters($placeId, $start)
                ->getSingleRow();

            if ($sunForecastRow !== NULL) {
                return new Sun($sunForecastRow["sunrise"], $sunForecastRow["sunset"], $sunForecastRow["start_sun_altitude"], 
                    $sunForecastRow["end_sun_altitude"], $sunForecastRow["start_sun_azimuth"], $sunForecastRow["end_sun_azimuth"]);
            }

            return NULL;
        }

        // TODO: Accept PlaceIdentifier + start instead.
        public function updateDaylightForecast($placeId, $start, $end, $latitude, $longitude) : void {
            global $databaseProvider;

            $dateTime = new DateTime();
            $dateTime->setTimestamp(intval($start));
            $suncalc = new AurorasLive\SunCalc($dateTime, $latitude, $longitude);
            $sunTimes = $suncalc->getSunTimes();
            $startSunPosition = $suncalc->getSunPosition($dateTime);
            $dateTime->setTimestamp(intval($end));
            $endSunPosition = $suncalc->getSunPosition($dateTime);
            
            $databaseProvider
                ->statementBuilder("DELETE FROM forecast_daylight WHERE place_id = ? AND timestamp = ?")
                ->withParameters($placeId, $start)
                ->execute();

            $databaseProvider
                ->statementBuilder("INSERT INTO forecast_daylight (place_id, timestamp, sunrise, sunset, start_sun_altitude, end_sun_altitude, start_sun_azimuth, end_sun_azimuth) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->withParameters($placeId, $start, $sunTimes["sunrise"]->getTimestamp(), $sunTimes["sunset"]->getTimestamp(), $startSunPosition->altitude * 180 / M_PI, $endSunPosition->altitude * 180 / M_PI, $startSunPosition->azimuth * 180 / M_PI, $endSunPosition->azimuth * 180 / M_PI)
                ->execute();
        }

        // TODO: Accept PlaceIdentifier + start instead.
        public function updateHistoricalWeatherForecast($placeId, $start, $latitude, $longitude) : void {
            global $databaseProvider, $configuration, $httpClient;
            
            $timestamp = intval($start);
            $oneYearAgoTimestamp = $timestamp;
            while ($oneYearAgoTimestamp > (time() - 10 * 86400)) {
                $oneYearAgoTimestamp -= 86400 * 365;
            } 
    
            $startDate = date("Y-m-d", $oneYearAgoTimestamp - 3 * 86400);
            $endDate = date("Y-m-d", $oneYearAgoTimestamp + 3 * 86400);
        
            $apiResponse = $httpClient->executeRequest("GET", "https://archive-api.open-meteo.com/v1/archive?latitude=" . $latitude . "&longitude=" . $longitude . "&start_date=" . $startDate . "&end_date=" . $endDate . "&daily=temperature_2m_max,precipitation_sum,windspeed_10m_max&timezone=" . $configuration["homeLocation"]["timezone"] . "&windspeed_unit=ms&timeformat=unixtime");
            
            $result = array(
                "temperature" => $this->getAverage($apiResponse["daily"]["temperature_2m_max"]),
                "wind" => $this->getAverage($apiResponse["daily"]["windspeed_10m_max"]),
                "precipitation" => $this->getAverage($apiResponse["daily"]["precipitation_sum"]) / 24);
    
            if ($result["temperature"] !== NULL && $result["wind"] !== NULL && $result["precipitation"] !== NULL) {    
                $databaseProvider
                    ->statementBuilder("DELETE FROM forecast_historical WHERE place_id = ? AND timestamp = ?")
                    ->withParameters($placeId, $timestamp)
                    ->execute();

                $databaseProvider
                    ->statementBuilder("INSERT INTO forecast_historical (place_id, timestamp, temperature, wind, precipitation) VALUES (?, ?, ?, ?, ?)")
                    ->withParameters($placeId, $timestamp, $result["temperature"], $result["wind"], $result["precipitation"])
                    ->execute();
            }
        }

        // TODO: Accept PlaceIdentifier + start instead.
        public function updateActualWeatherForecast($placeId, $start, $latitude, $longitude) : void {
            global $databaseProvider, $configuration, $httpClient;
        
            $apiResponse = $httpClient->executeRequest("GET", "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=" . round($latitude, 4) . "&lon=" . round($longitude, 4),
                array("User-Agent: " . BASE_URL . " " . $configuration["contactEmail"]), NULL, TRUE);

            if (!isset($apiResponse["properties"]) || !isset($apiResponse["properties"]["timeseries"]) || $apiResponse["properties"]["timeseries"] == NULL) {
                throw new RuntimeException("Unable to fetch the forecast. Response: " . json_encode($apiResponse));
            }

            $bestForecast = NULL;
            foreach ($apiResponse["properties"]["timeseries"] as &$forecast) {
                $forecastTime = strtotime($forecast["time"]);
                if ($forecastTime > intval($start)) {
                    break;
                }
                $bestForecast = $forecast;
            }         

            if ((strtotime($bestForecast["time"]) + 21600) < intval($start)) {
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
                    ->withParameters($placeId, $start)
                    ->execute();

                $databaseProvider
                    ->statementBuilder("INSERT INTO forecast_actual (place_id, timestamp, temperature, wind, precipitation, clouds, symbol, last_update, expiration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->withParameters($placeId, $start, $convertedForecast["temperature"], $convertedForecast["wind"], $convertedForecast["precipitation"], $convertedForecast["clouds"], $convertedForecast["symbol"], $convertedForecast["updatedAt"], (isset($apiResponse["__httpHeaders"]["Expires"]) ? strtotime($apiResponse["__httpHeaders"]["Expires"]) : (time() + 3600)))
                    ->execute();
            }
        }
    
        private function getAverage($values) {
            $sum = 0;
            $count = 0;
            foreach ($values as &$value) {
                if ($value !== NULL) {
                    $sum += $value;
                    $count += 1;
                }
            }
            if ($count === 0) {
                return NULL;
            }
            return $sum / $count;
        }
    }
?>