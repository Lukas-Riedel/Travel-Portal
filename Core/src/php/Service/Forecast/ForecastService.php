<?php
    namespace Core\Service\Forecast;
    
    use AurorasLive\SunCalc;
    use Core\Common\CommonConstants;
    use Core\Service\Configuration\ConfigurationService;
    use Core\Service\Place\PlaceIdentifier;
    use Core\Client\Database\DatabaseClient;
    use Core\Client\Database\TransactionManager;
    use Common\Client\Http\HttpMethod;
    use Core\Client\Forecast\ForecastClient;

    class ForecastService {

        private const HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER = 2;

        private readonly ForecastMapper $forecastMapper;

        private readonly ConfigurationService $configurationService;

        private readonly ForecastClient $actualForecastClient;
        private readonly ForecastClient $historicalForecastClient;

        private readonly TransactionManager $transactionManager;

        public function __construct(DatabaseClient $databaseClient, ConfigurationService $configurationService,
            ForecastClient $actualForecastClient, ForecastClient $historicalForecastClient) {
            $this->forecastMapper = new ForecastMapper($databaseClient);
            $this->configurationService = $configurationService;
            $this->actualForecastClient = $actualForecastClient;
            $this->historicalForecastClient = $historicalForecastClient;
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

            $historicalForecast = $this->historicalForecastClient->getForecast($placeIdentifier->getLatitude(), $placeIdentifier->getLongitude(),
                $oneYearAgoTimestamp - self::HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER * CommonConstants::ONE_DAY_SECONDS,
                $oneYearAgoTimestamp + self::HISTORICAL_WEATHER_FORECAST_DAYS_BEFORE_AND_AFTER * CommonConstants::ONE_DAY_SECONDS);
            if ($historicalForecast === null) {
                return;
            }
    
            $this->transactionManager->executeAtomically(function() use (&$placeIdentifier, &$historicalForecast, &$timestamp) {
                $this->forecastMapper->deleteHistoricalWeatherForecast($placeIdentifier->getId(), $timestamp);
                $this->forecastMapper->insertHistoricalWeatherForecast($historicalForecast, $placeIdentifier->getId(), $timestamp);                
            });
            $this->forecastMapper->deleteStaleHistoricalWeatherForecast();
        }

        public function updateActualWeatherForecast(PlaceIdentifier $placeIdentifier, int $timestamp) : void {    
            $actualForecast = $this->actualForecastClient->getForecast($placeIdentifier->getLatitude(), 
                $placeIdentifier->getLongitude(), $timestamp, $timestamp);
            if ($actualForecast === null) {
                return;
            }

            $this->transactionManager->executeAtomically(function() use (&$placeIdentifier, &$actualForecast, &$timestamp) {
                $this->forecastMapper->deleteActualWeatherForecast($placeIdentifier->getId(), $timestamp);
                $this->forecastMapper->insertActualWeatherForecast($actualForecast, $placeIdentifier->getId(), $timestamp);
            });

            $this->forecastMapper->deleteStaleActualWeatherForecast();
        }
    }
?>