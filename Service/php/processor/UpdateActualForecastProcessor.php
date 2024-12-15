<?php
    require_once(dirname(__FILE__) . "/GetHttpResponseProcessor.php");

    class UpdateActualForecastProcessor extends Processor {        
        public function process($input) {
            global $databaseProvider, $configuration;

            $placeIdentifier = $databaseProvider
                ->statementBuilder("SELECT * FROM place_identifier WHERE id = ?")
                ->withParameters($input["placeId"])
                ->getSingleRow();
        
            $apiResponse = (new GetHttpResponseProcessor())
                ->process(array(
                    "method" => "GET", 
                    "url" => "https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=" . round($placeIdentifier["latitude"], 4) . "&lon=" . round($placeIdentifier["longitude"], 4), 
                    "includeHeaders" => TRUE,
                    "headers" => "User-Agent: " . $configuration["hostName"] . " " . $configuration["contactEmail"]));

            if (!isset($apiResponse["properties"]) || !isset($apiResponse["properties"]["timeseries"]) || $apiResponse["properties"]["timeseries"] == NULL) {
                throw new RuntimeException("Unable to fetch the forecast. Response: " . json_encode($apiResponse));
            }

            $bestForecast = NULL;
            foreach ($apiResponse["properties"]["timeseries"] as &$forecast) {
                $forecastTime = strtotime($forecast["time"]);
                if ($forecastTime > intval($input["start"])) {
                    break;
                }
                $bestForecast = $forecast;
            }         

            if ((strtotime($bestForecast["time"]) + 21600) < intval($input["start"])) {
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
                    ->withParameters($placeIdentifier["id"], $input["start"])
                    ->execute();

                $databaseProvider
                    ->statementBuilder("INSERT INTO forecast_actual (place_id, timestamp, temperature, wind, precipitation, clouds, symbol, last_update, expiration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                    ->withParameters($placeIdentifier["id"], $input["start"], $convertedForecast["temperature"], $convertedForecast["wind"], $convertedForecast["precipitation"], $convertedForecast["clouds"], $convertedForecast["symbol"], (isset($input["updatedAt"]) ? $input["updatedAt"] : $convertedForecast["updatedAt"]), (isset($apiResponse["__httpHeaders"]["Expires"]) ? strtotime($apiResponse["__httpHeaders"]["Expires"]) : (time() + 3600)))
                    ->execute();

                return TRUE;
            }

            return FALSE;
        }

        public function getRequiredArguments() {
            return array("placeId", "start");
        }
        
        public function requiresAdminRole() {
            return TRUE;
        }
    }
?>