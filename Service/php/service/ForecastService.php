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
    }
?>