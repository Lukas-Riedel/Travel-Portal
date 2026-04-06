<?php
    namespace Core\Service\Forecast;
    
    use Core\Client\Database\DatabaseClient;
    use Core\Common\CommonConstants;

    class ForecastMapper {

        private readonly DatabaseClient $databaseClient;

        public function __construct(DatabaseClient $databaseClient) {
            $this->databaseClient = $databaseClient;
        }

        public function selectActualWeatherForecast(string $placeId, int $timestamp) : ?Weather {
            $sql = <<<'SQL'
                SELECT *
                FROM forecast_actual
                WHERE place_id = ?
                    AND timestamp = ?
            SQL;

            $forecastRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $timestamp)
                ->getSingleRow();

            return $forecastRow === null ? null : new Weather($forecastRow["temperature"], $forecastRow["clouds_total"] !== null
                ? new Clouds($forecastRow["clouds_total"], $forecastRow["clouds_low"], $forecastRow["clouds_medium"], $forecastRow["clouds_high"]) : null,
                $forecastRow["wind"], new Precipitation($forecastRow["precipitation"], $forecastRow["precipitation_probability"]), $forecastRow["humidity"],
                $forecastRow["last_update"], $forecastRow["expiration"]);
        }

        public function selectHistoricalWeatherForecast(string $placeId, int $timestamp) : ?Weather {
            $sql = <<<'SQL'
                SELECT *
                FROM forecast_historical
                WHERE place_id = ?
                    AND timestamp = ?
            SQL;

            $forecastRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $timestamp)
                ->getSingleRow();

            return $forecastRow === null ? null : new Weather($forecastRow["temperature"], null, $forecastRow["wind"],
                new Precipitation($forecastRow["precipitation"], null), null, time(), $timestamp + CommonConstants::ONE_YEAR_SECONDS);
        }

        public function selectDaylightForecast(string $placeId, int $timestamp) : ?Sun {
            $sql = <<<'SQL'
                SELECT *
                FROM forecast_daylight
                WHERE place_id = ?
                    AND timestamp = ?
            SQL;

            $forecastRow = $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $timestamp)
                ->getSingleRow();

            return $forecastRow === null ? null : new Sun($forecastRow["sunrise"], $forecastRow["sunset"], $forecastRow["start_sun_altitude"], 
                $forecastRow["end_sun_altitude"], $forecastRow["start_sun_azimuth"], $forecastRow["end_sun_azimuth"]);
        }

        public function selectActualWeatherForecastExpiration(string $placeId, int $timestamp) : ?int {
            $sql = <<<'SQL'
                SELECT expiration
                FROM forecast_actual
                WHERE place_id = ?
                    AND timestamp = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $timestamp)
                ->getSingleColumn("expiration");
        }

        public function insertDaylightForecast(Sun $sun, string $placeId, int $timestamp) : bool {
            $sql = <<<'SQL'
                INSERT INTO forecast_daylight (
                    place_id, 
                    timestamp, 
                    sunrise, 
                    sunset, 
                    start_sun_altitude, 
                    end_sun_altitude, 
                    start_sun_azimuth, 
                    end_sun_azimuth
                )
                VALUES (
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $timestamp, $sun->getSunrise(), $sun->getSunset(), $sun->getAltitude()->getStart(),
                    $sun->getAltitude()->getEnd(), $sun->getAzimuth()->getStart(), $sun->getAzimuth()->getEnd())
                ->execute() === 1;
        }

        public function insertHistoricalWeatherForecast(Weather $weather, string $placeId, int $timestamp) : bool {
            $sql = <<<'SQL'
                INSERT INTO forecast_historical (
                    place_id, 
                    timestamp, 
                    temperature, 
                    wind, 
                    precipitation
                )
                VALUES (
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $timestamp, $weather->getTemperature(), $weather->getWind(), $weather->getPrecipitation()->getTotal())
                ->execute() === 1;
        }

        public function insertActualWeatherForecast(Weather $weather, string $placeId, int $timestamp) : bool {
            $sql = <<<'SQL'
                INSERT INTO forecast_actual (
                    place_id, 
                    timestamp, 
                    temperature, 
                    wind, 
                    precipitation,
                    precipitation_probability,
                    humidity,
                    clouds_total, 
                    clouds_low, 
                    clouds_medium, 
                    clouds_high, 
                    last_update, 
                    expiration
                )
                VALUES (
                    ?, 
                    ?, 
                    ?, 
                    ?, 
                    ?,
                    ?,
                    ?, 
                    ?, 
                    ?, 
                    ?,
                    ?, 
                    ?, 
                    ?
                )
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $timestamp, $weather->getTemperature(), $weather->getWind(), $weather->getPrecipitation()->getTotal(), $weather->getPrecipitation()->getProbability(),
                    $weather->getHumidity(), $weather->getClouds()?->getTotal(), $weather->getClouds()?->getLow(), $weather->getClouds()?->getMedium(), $weather->getClouds()?->getHigh(),
                    $weather->getLastUpdate(), $weather->getValidity())
                ->execute() === 1;
        }

        public function deleteDaylightForecast(string $placeId, int $timestamp) : int {
            $sql = <<<'SQL'
                DELETE
                FROM forecast_daylight
                WHERE place_id = ?
                    AND timestamp = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $timestamp)
                ->execute();
        }

        public function deleteHistoricalWeatherForecast(string $placeId, int $timestamp) : int {
            $sql = <<<'SQL'
                DELETE
                FROM forecast_historical
                WHERE place_id = ?
                    AND timestamp = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $timestamp)
                ->execute();
        }

        public function deleteActualWeatherForecast(string $placeId, int $timestamp) : int {
            $sql = <<<'SQL'
                DELETE
                FROM forecast_actual
                WHERE place_id = ?
                    AND timestamp = ?
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->withParameters($placeId, $timestamp)
                ->execute();
        }

        public function deleteStaleActualWeatherForecast() : int {
            $sql = <<<'SQL'
                DELETE
                FROM forecast_actual
                WHERE timestamp < ROUND(EXTRACT(EPOCH FROM NOW()));
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }

        public function deleteStaleHistoricalWeatherForecast() : int {
            $sql = <<<'SQL'
                DELETE
                FROM forecast_historical
                WHERE timestamp < ROUND(EXTRACT(EPOCH FROM NOW()));
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }

        public function deleteStaleDaylightForecast() : int {
            $sql = <<<'SQL'
                DELETE
                FROM forecast_daylight
                WHERE timestamp < ROUND(EXTRACT(EPOCH FROM NOW()));
            SQL;

            return $this->databaseClient
                ->statementBuilder($sql)
                ->execute();
        }
    }
?>