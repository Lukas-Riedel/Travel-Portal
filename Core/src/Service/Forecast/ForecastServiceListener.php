<?php
    namespace Core\Service\Forecast;

    use Core\Common\CommonConstants;
    use Core\Service\Place\PlaceService;
    use Core\Service\Place\PlaceIncludedEntity;
    use Core\Service\Place\PlaceSortingStrategy;
    use Core\Event\Event;
    use Core\Event\EventPublisher;
    use Core\Event\Scheduler;

    class ForecastServiceListener {

        private const FETCH_ACTUAL_WEATHER_FORECAST_ACTION_NAME = "FETCH_ACTUAL_WEATHER_FORECAST";
        private const FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_NAME = "FETCH_HISTORICAL_WEATHER_FORECAST";
        private const FETCH_DAYLIGHT_FORECAST_ACTION_NAME = "FETCH_DAYLIGHT_FORECAST";
        private const FETCH_ACTUAL_WEATHER_FORECAST_ACTION_INTERVAL = 300;
        private const FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_INTERVAL = 300;
        private const FETCH_DAYLIGHT_FORECAST_ACTION_INTERVAL = 300;

        private readonly ForecastService $forecastService;
        private readonly PlaceService $placeService;
        private readonly EventPublisher $eventPublisher;
        private readonly Scheduler $scheduler;

        private readonly int $actualWeatherForecastDaysToCache;

        public function __construct(ForecastService $forecastService, PlaceService $placeService, EventPublisher $eventPublisher, Scheduler $scheduler, int $actualWeatherForecastDaysToCache) {
            $this->forecastService = $forecastService;
            $this->placeService = $placeService;
            $this->eventPublisher = $eventPublisher;
            $this->scheduler = $scheduler;
            $this->actualWeatherForecastDaysToCache = $actualWeatherForecastDaysToCache;
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
                    if (time() + $this->actualWeatherForecastDaysToCache * CommonConstants::ONE_DAY_SECONDS > $date->getStart()) {
                        $this->eventPublisher->publish(Event::ActualWeatherForecastUpdated($place->getId(), $date->getStart()));
                    }
                            
                    $this->eventPublisher->publish(Event::HistoricalWeatherForecastUpdated($place->getId(), $date->getStart()));
                    $this->eventPublisher->publish(Event::DaylightForecastUpdated($place->getId(), $date->getStart(), $date->getEnd()));
                }
            }
        }

        public function onPlaceEventUpdated(mixed $message) : void {
            $place = $this->placeService->getRegularPlace($message["placeId"]);
            foreach ($place->getDates() as &$date) {
                if (time() < $date->getStart()) {
                    if (time() + $this->actualWeatherForecastDaysToCache * CommonConstants::ONE_DAY_SECONDS > $date->getStart()) {
                        $this->eventPublisher->publish(Event::ActualWeatherForecastUpdated($place->getId(), $date->getStart()));
                    }
                            
                    $this->eventPublisher->publish(Event::HistoricalWeatherForecastUpdated($place->getId(), $date->getStart()));
                    $this->eventPublisher->publish(Event::DaylightForecastUpdated($place->getId(), $date->getStart(), $date->getEnd()));
                }
            }
        }

        public function onActualForecastWatchingTriggered(mixed $message) : void {
            if ($message["end"] <= time() + $this->actualWeatherForecastDaysToCache * CommonConstants::ONE_DAY_SECONDS) {
                $publishTimestamp = time();
                while ($publishTimestamp < $message["end"]) {
                    $timestamp = $message["start"];
                    while ($timestamp < $message["end"]) {
                        $this->eventPublisher->publish(Event::ActualWeatherForecastUpdated($message["placeId"], $timestamp), $publishTimestamp);
                        $timestamp += CommonConstants::ONE_HOUR_SECONDS;
                    }
                    $publishTimestamp += self::FETCH_ACTUAL_WEATHER_FORECAST_ACTION_INTERVAL;
                }
            }
        }

        public function onSchedulerTriggered(mixed $message) : void {
            if ($this->scheduler->requestExecution(self::FETCH_ACTUAL_WEATHER_FORECAST_ACTION_NAME, self::FETCH_ACTUAL_WEATHER_FORECAST_ACTION_INTERVAL)) {
                $places = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, time(),
                    time() + $this->actualWeatherForecastDaysToCache * CommonConstants::ONE_DAY_SECONDS, null, null, array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {
                        $timestamp = $date->getStart();
                        while ($timestamp < $date->getEnd()) {
                            if ($this->forecastService->isActualWeatherForecastExpired($place->getId(), $timestamp)) {
                                $this->eventPublisher->publish(Event::ActualWeatherForecastUpdated($place->getId(), $timestamp));
                            }

                            $timestamp += CommonConstants::ONE_HOUR_SECONDS;
                        }
                    }
                }
            }

            if ($this->scheduler->requestExecution(self::FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_NAME, self::FETCH_HISTORICAL_WEATHER_FORECAST_ACTION_INTERVAL)) {
                $places = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, time(), null, null, null,
                    array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);
        
                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {
                        if (empty($date->getWeather())) {
                            $this->eventPublisher->publish(Event::HistoricalWeatherForecastUpdated($place->getId(), $date->getStart()));
                        }
                    }
                }
            }

            if ($this->scheduler->requestExecution(self::FETCH_DAYLIGHT_FORECAST_ACTION_NAME, self::FETCH_DAYLIGHT_FORECAST_ACTION_INTERVAL)) {
                $places = $this->placeService->getRegularPlaces(null, null, null, null, null, null, null, null, time(), null, null, null,
                    array(PlaceIncludedEntity::Dates->value), PlaceSortingStrategy::OldestAscending);

                foreach ($places as &$place) {
                    foreach ($place->getDates() as &$date) {    
                        if ($date->getSun() === null) {
                            $this->eventPublisher->publish(Event::DaylightForecastUpdated($place->getId(), $date->getStart(), $date->getEnd()));
                        }
                    }
                }
            }
        }
    }
?>