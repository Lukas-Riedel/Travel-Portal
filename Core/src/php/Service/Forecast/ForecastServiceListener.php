<?php
    namespace Core\Service\Forecast;

    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceSortingStrategy;

    class ForecastServiceListener {

        private const FETCH_ACTUAL_WEATHER_FORECAST_ACTION_NAME = "FETCH_ACTUAL_WEATHER_FORECAST";
        private const FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_NAME = "FETCH_HISTORICAL_WEATHER_FORECAST";
        private const FETCH_DAYLIGHT_FORECAST_ACTION_NAME = "FETCH_DAYLIGHT_FORECAST";
        private const FETCH_ACTUAL_WEATHER_FORECAST_ACTION_INTERVAL = 300;
        private const FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_INTERVAL = 300;
        private const FETCH_DAYLIGHT_FORECAST_ACTION_INTERVAL = 300;
        private const ACTUAL_WEATHER_FORECAST_DAYS_TO_CACHE = 9;

        private readonly ForecastService $forecastService;

        private readonly PlaceService $placeService;

        private readonly \EventPublisher $eventPublisher;
        private readonly \Scheduler $scheduler;

        public function __construct(ForecastService $forecastService, PlaceService $placeService, \EventPublisher $eventPublisher, \Scheduler $scheduler) {
            $this->forecastService = $forecastService;
            $this->placeService = $placeService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
        }

        public function onActualWeatherForecastUpdated(mixed $message) : void {
            $placeIdentifier = $this->placeService->getPlaceIdentifierById($message["placeId"]);
            $this->forecastService->updateActualWeatherForecast($placeIdentifier, $message["start"]);
        }

        public function onHistoricalWeatherForecastUpdated(mixed $message) : void {
            $placeIdentifier = $this->placeService->getPlaceIdentifierById($message["placeId"]);
            $this->forecastService->updateHistoricalWeatherForecast($placeIdentifier, $message["start"]);
        }

        public function onDaylightForecastUpdated(mixed $message) : void {
            $placeIdentifier = $this->placeService->getPlaceIdentifierById($message["placeId"]);
            $this->forecastService->updateDaylightForecast($placeIdentifier, $message["start"], $message["end"]);
        }

        public function onPlaceEventCreated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            foreach ($place->getDates() as &$date) {
                if (time() < $date->getStart()) {
                    if (time() + self::ACTUAL_WEATHER_FORECAST_DAYS_TO_CACHE * 86400 > $date->getStart()) {
                        $this->eventPublisher->publishActualWeatherForecastUpdated($place->getId(), $date->getStart());
                    }
                            
                    $this->eventPublisher->publishHistoricalWeatherForecastUpdated($place->getId(), $date->getStart());
                    $this->eventPublisher->publishDaylightForecastUpdated($place->getId(), $date->getStart(), $date->getEnd());
                }
            }
        }

        public function onPlaceEventUpdated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            foreach ($place->getDates() as &$date) {
                if (time() < $date->getStart()) {
                    if (time() + self::ACTUAL_WEATHER_FORECAST_DAYS_TO_CACHE * 86400 > $date->getStart()) {
                        $this->eventPublisher->publishActualWeatherForecastUpdated($place->getId(), $date->getStart());
                    }
                            
                    $this->eventPublisher->publishHistoricalWeatherForecastUpdated($place->getId(), $date->getStart());
                    $this->eventPublisher->publishDaylightForecastUpdated($place->getId(), $date->getStart(), $date->getEnd());
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::FETCH_ACTUAL_WEATHER_FORECAST_ACTION_NAME, self::FETCH_ACTUAL_WEATHER_FORECAST_ACTION_INTERVAL)) {
                $places = $this->placeService->getRegularPlaces(NULL, NULL, NULL, NULL, NULL, NULL, NULL, time(),
                    time() + self::ACTUAL_WEATHER_FORECAST_DAYS_TO_CACHE * 86400, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::Default);

                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {
                        if ($this->forecastService->isActualWeatherForecastExpired($place->getId(), $date->getStart())) {
                            $this->eventPublisher->publishActualWeatherForecastUpdated($place->getId(), $date->getStart());
                        }
                    }
                }
            }

            if ($this->scheduler->requestExecution(self::FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_NAME, self::FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_INTERVAL)) {
                $places = $this->placeService->getRegularPlaces(NULL, NULL, NULL, NULL, NULL, NULL, NULL, time(), NULL,
                    array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::Default);
        
                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {    
                        if ($date->getWeather() === NULL) {
                            $this->eventPublisher->publishHistoricalWeatherForecastUpdated($place->getId(), $date->getStart());
                        }
                    }
                }
            }

            if ($this->scheduler->requestExecution(self::FETCH_DAYLIGHT_FORECAST_ACTION_NAME, self::FETCH_DAYLIGHT_FORECAST_ACTION_INTERVAL)) {
                $places = $this->placeService->getRegularPlaces(NULL, NULL, NULL, NULL, NULL, NULL, NULL, time(), NULL,
                    array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::Default);

                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {    
                        if ($date->getSun() === NULL) {
                            $this->eventPublisher->publishDaylightForecastUpdated($place->getId(), $date->getStart(), $date->getEnd());
                        }
                    }
                }
            }
        }
    }
?>